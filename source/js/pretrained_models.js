$(document).ready(function() {
    // Check if the user is logged in by looking for the token
    var token = localStorage.getItem('token');

    // If no token is found, redirect to the login page
    if (!token) {
        window.location.href = './login.html';
        return; // Prevent further execution of the script
    }

    // Fetch user data from session storage
    var fname = localStorage.getItem('fname');
    var lname = localStorage.getItem('lname');

    // Hide loading buttons
    $('#loadDnloadBtn, #loadDelBtn, #loadDelModelBtn, #loadSelectedModelBtn, #loadUploadUnclassifiedDatasetBtn, #loadDnloadUnclassifiedBtn, #loadDelUnclassifiedModelBtn, #loadDelUnclassifiedBtn, #loadDnloadClassifiedBtn, #loadUnclassifiedDatasetBtn, #loadUploadingUnclassifiedDatasetBtn, #loadDelUnclassifiedDtBtn, #loadClassifyDtBtn, #loadExportBtn').hide();

    // Hide the other windows except for selectind pretrained model
    $('#parametersSelected, #dtUnclassified, #tableUnclassifiedDt, #classifiedDt').hide();

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            placement: 'right' // Place the tooltip to the right
        })
    });

    // User is logged in
    $('#loginBtn').hide();
    $('#registerBtn').hide();
    $('#profileNav').show();
    $('#username').text(fname + ' ' + lname);

    // Logout functionality
    $('#logoutBtn').on('click', function() {
        localStorage.removeItem('token');
        localStorage.removeItem('fname');
        localStorage.removeItem('lname');
        window.location.href = '../index.html'; // Redirect to home page
    });

    // Function to display the alert Datasets' message
    function showAlert(type, message, id) {
        var alertMessage = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $(id).html(alertMessage); // Display alert in the datasets section
    }

    function loadModels() {
        // API call for loading models
        $.ajax({
            url: '../server/php/api/list_models.php',
            method: 'GET',
            data: {
                token: token
            },
            success: function(response) {
                if (response.error) {
                    showAlert('danger', response.error, '#alertModels');
                    return;
                }
                
                var models = response.models || [];
                var $selectModel = $('#selectModel');

                $selectModel.empty(); // Clear any existing options
                $selectModel.append('<option value="default" selected>Select model</option>');
                
                models.forEach(function(model) {
                    $selectModel.append(`<option value="${model.path}">[Model] ${model.name}</option>`);
                });
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = JSON.parse(xhr.responseText);
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertModels');
            }
        });
    }

    // Function to load the models
    loadModels();

    // Global variables: chosen model file, selected model option, features, and class
    let selectedModelOption = null;
    let model_file = '';
    let model_features = [];
    let model_class = '';

    // Event listener for dataset selection
    $('#selectModel').on('change', function() {
        $('#alertPreviewParams').html('');
        $('#individualFeatures').empty();
        $('#selectClass').empty();

        selectedModelOption = $('#selectModel option:selected');
        model_file = selectedModelOption.val().split('/').pop();
        
        if (model_file && model_file !== 'default') {
            $('#dnloadBtn').prop('disabled', false);
            $('#delBtn').prop('disabled', false);
            $('#parametersSelected').show();    
            $('#dtUnclassified').show();
        } else {
            $('#dnloadBtn').prop('disabled', true);
            $('#delBtn').prop('disabled', true);
            $('#parametersSelected, #dtUnclassified').hide();    
            $('#tableUnclassifiedDt, #classifiedDt').hide();
        }

        if (model_file && token) {
            // Show loading button
            $('#loadSelectedModelBtn').show();

            // API call for loading the chosen model's features and class
            $.ajax({
                url: '../server/php/api/get_model_content.php',
                method: 'GET',
                data: {
                    token: token,
                    model: model_file,
                },
                contentType: 'application/json',
                success: function(response) {
                    // Check if features is an array
                    if (Array.isArray(response.features)) {
                        model_features = response.features;
                        displayModelFeatures(model_features);
                    } else {
                        $('#individualFeatures').html('<p>Failed to retrieve features.</p>');
                    }
                
                    // Check if class is defined
                    if (response.class) {
                        model_class = response.class;
                        displayModelClass(model_class);
                    } else {
                        $('#selectClass').html('<option value="default" selected>Failed to retrieve a class</option>');
                    }
                },                
                error: function(xhr, status, error) {
                    // Handle error during the kNN execution
                    console.log('Error:', error);
                    console.log('XHR object:', xhr);
                    console.log('Status:', status);

                    // Display specific error message from the server response
                    const response = JSON.parse(xhr.responseText);
                    const message = response && response.message ? response.message : 'An unexpected error occurred.';

                    showAlert('danger', 'Failed to load model content.', '#alertPreviewParams');
                },
                complete: function() {
                    $('#loadSelectedModelBtn').hide();
                }
            });
        }
    });

    // Function to sanitize and display model features
    function displayModelFeatures(features) {
        if (!Array.isArray(features) || features.length === 0) {
            $('#individualFeatures').html('<p>Failed to retrieve features.</p>');
            return;
        }

        // Clean the features array to remove extra quotes or spaces
        let sanitizedFeatures = features.map(feature => feature.trim().replace(/['"]/g, ''));

        // Generate form HTML for the features
        let formHtml = '';
        sanitizedFeatures.forEach(feature => {
            formHtml += `
                <div class="form-check form-check-inline">
                    <input class="form-check-input feature-checkbox" type="checkbox" value="${feature}" id="${feature}" checked disabled>
                    <label class="form-check-label" for="${feature}">
                        ${feature}
                    </label>
                </div>
            `;
        });

        // Display the sanitized features in the target element
        $('#individualFeatures').html(formHtml);
    }

    // Function to display the class in the select dropdown
    function displayModelClass(modelClass) {
        if (!modelClass) {
            $('#selectClass').html('<option value="default" selected>Failed to retrieve a class</option>');
            return;
        }

        // Clean and display the class
        let sanitizedClass = modelClass.trim().replace(/['"]/g, '');
        $('#selectClass').html(`<option selected disabled>${sanitizedClass}</option>`);
    }

    // Handle event when download button is pressed
    $('#dnloadBtn').on('click', function() {
        $('#dnloadBtn').hide();
        $('#loadDnloadBtn').show();

        // API call for downloading the model
        window.location.href = '../server/php/api/download_model.php?token=' + token + '&model=' + model_file;

        $('#dnloadBtn').show();
        $('#loadDnloadBtn').hide();       
    });

    // Store the dataset to be deleted
    $('#delBtn').on('click', function() {
        $('#loadDelBtn').show();
        $('#delBtn').hide();
    });

    // Handle delete confirmation
    $('#delModelBtn').on('click', function() {
        $('#delModelBtn').hide();
        $('#loadDelModelBtn').show();
        
        // API call for deleting the model
        $.ajax({
            url: '../server/php/api/delete_model.php',
            method: 'DELETE',
            data: JSON.stringify({
                token: token,
                model: model_file
            }),
            contentType: 'application/json',
            success: function(response) {
                $('#delModelBtn').show();
                $('#loadDelModelBtn').hide();
                $('#delBtn').show();
                $('#loadDelBtn').hide();
                $('#selectModel').val('default');
                $('#delModelBtn').prop('disabled', true);
                $('#delBtn').prop('disabled', true);
                $('#dnloadBtn').prop('disabled', true)
                $('#alertPreview').html('');
                $('#parametersSelected').hide();
                $('#dtUnclassified').hide();
                $('#tableUnclassifiedDt').hide();
                $('#classifiedDt').hide();
                
                showAlert('success', response.message, '#alertDelModal');
                loadModels();
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#delModelBtn').show();
                $('#loadDelModelBtn').hide();
                $('#delBtn').show();
                $('#loadDelBtn').hide();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                // Show error alert
                showAlert('danger', message, '#alertDelModal');
            }
        });
    });

    // When I close the delete modal window
    $('#closeDelModalBtn').on('click', function() {
        $('#delModelBtn').prop('disabled', false);
        $('#loadDelBtn').hide();
        $('#delBtn').show();
        $('#alertDelModal').html('');
    });

    function loadUnclassifiedDatasets() {
        // API call for loading unclassified datasets
        $.ajax({
            url: '../server/php/api/list_unclassified_datasets.php',
            method: 'GET',
            data: {
                token: token
            },
            success: function(response) {
                if (response.error) {
                    showAlert('danger', response.error, '#alertUnclassifiedDatasets');
                    return;
                }
                
                var unclassifiedDatasets = response.unclassifiedDatasets || [];
                var $selectUnclassifiedDataset = $('#selectUnclassifiedDataset');

                $selectUnclassifiedDataset.empty(); // Clear any existing options
                $selectUnclassifiedDataset.append('<option value="default" selected>Select unclassified dataset</option>');
                
                unclassifiedDatasets.forEach(function(UnclassifiedDataset) {
                    $selectUnclassifiedDataset.append(`<option value="${UnclassifiedDataset.path}">[Unclassified dataset] ${UnclassifiedDataset.name}</option>`);
                });
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = JSON.parse(xhr.responseText);
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', 'Failed to load models.', '#alertUnclassifiedDatasets');
            }
        });
    }

    // Function to load the unclassified datasets
    loadUnclassifiedDatasets();

    // Event handler for the Upload modal button
    $('#uploadBtn').on('click', function() {
        $('#loadUploadUnclassifiedDatasetBtn').show();
    });

    // Event handler for the Upload modal close button
    $('#closeUploadUnclassifiedModalBtn').on('click', function () {
        // Clear file input
        $('#inputGroupFile').val('');

        // Clear any existing alerts in the modal
        $('#alertUploadUnclassifiedModal').html('');

        // Reset the upload and loading buttons
        $('#uploadUnclassifiedBtn').show();
        $('#loadUploadingUnclassifiedDatasetBtn').hide();
        $('#loadUploadUnclassifiedDatasetBtn').hide();
    });
    
    // When the x button is pressed at upload modal window
    $('#closeXBtn').on('click', function() {
        $('#inputGroupFile').val('');

        $('#alertUploadUnclassifiedModal').html('');

        $('#uploadUnclassifiedBtn').show();
        $('#loadUploadingUnclassifiedDatasetBtn').hide();
        $('#loadUploadUnclassifiedDatasetBtn').hide();
    });

    function validateUpload() {
        var fileInput = $('#inputGroupFile')[0].files[0];

        // Validate file size
        if (fileInput && fileInput.size > 10 * 1024 * 1024) {
            showAlert('danger', 'File size exceeds 10MB limit.', '#alertUploadUnclassifiedModal');
            this.value = '';
            fileInput = null
            return false;
        }

        // Validate file type
        var allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
        if (fileInput && !allowedTypes.includes(fileInput.type)) {
            showAlert('danger', 'Invalid file type. Only CSV files are allowed.', '#alertUploadUnclassifiedModal');
            this.value = '';
            fileInput = null
            return false;
        }

        $('#uploadUnclassifiedBtn').prop('disabled', !(fileInput));
        return true;
    }

    $('#inputGroupFile').on('change', validateUpload);

    // Handle the file upload
    $('#uploadUnclassifiedBtn').on('click', function() {
        // Hide the upload button and show the loading button
        $('#uploadUnclassifiedBtn').hide();
        $('#loadUploadingUnclassifiedDatasetBtn').show();
        
        if (!validateUpload()) {
            return;
        }

        var fileInput = $('#inputGroupFile')[0].files[0];

        var formData = new FormData();
        formData.append('file', fileInput);
        formData.append('token', token);

        // API call for uploading an unclassified dataset
        $.ajax({
            url: '../server/php/api/upload_unclassified_dataset.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Show the upload button and hide the loading button
                $('#uploadUnclassifiedBtn').show();
                $('#loadUploadingUnclassifiedDatasetBtn').hide();
                $('#loadUploadUnclassifiedDatasetBtn').hide();
                $('#tableUnclassifiedDt').hide();
                $('#classifiedDt').hide();
                showAlert('success', response.message, '#alertUploadUnclassifiedModal');
                loadUnclassifiedDatasets();
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                // Show the upload button and hide the loading button
                $('#uploadUnclassifiedBtn').show();
                $('#loadUploadingUnclassifiedDatasetBtn').hide();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';
                
                showAlert('danger', message, '#alertUploadUnclassifiedModal');
            }
        });
    });

    // Global variables: chosen unclassified file selected unclassified option,
    let selectedUnclassifiedOption = null;
    let unclassified_file = '';
    let dtHeader = [];
    let dt = {
        header: [],
        data: []
    };

    // Event listener for dataset selection
    $('#selectUnclassifiedDataset').on('change', function() {
        $('#alertPreview').html('');

        selectedUnclassifiedOption = $('#selectUnclassifiedDataset option:selected');
        unclassified_file = selectedUnclassifiedOption.val().split('/').pop();

        if (unclassified_file && unclassified_file !== 'default') {
            $('#dnloadUnclassifiedBtn').prop('disabled', false);
            $('#delUnclassifiedBtn').prop('disabled', false);
            $('#tableUnclassifiedDt').show();
            $('#classifiedDt').hide();
        } else {
            $('#dnloadUnclassifiedBtn').prop('disabled', true);
            $('#delUnclassifiedBtn').prop('disabled', true);
            $('#tableUnclassifiedDt').hide();
            $('#classifiedDt').hide();
        }

        if (unclassified_file && token) {
            // Show loading button
            $('#loadUnclassifiedDatasetBtn').show();

            // API call for loading the chosen unclassified dataset's contents
            $.ajax({
                url: '../server/php/api/load_unclassified_dataset.php',
                method: 'GET',
                data: {
                    token: token,
                    file: unclassified_file,
                },
                success: function(response) {
                    // Hide loading button
                    $('#loadUnclassifiedDatasetBtn').hide();

                    if (response.message) {
                        // Show alert if there's a message
                        showAlert('danger', response.message, '#alertPreview');
                        return;
                    }

                    dt = {
                        header: response.header,
                        data: response.data
                    }
                    
                    // Filter and display numerical features with no missing values, and display classes on the select class tag
                    filterAndDisplayNumericalFeatures();

                    // Populate table with dataset contents
                    populateTable(dt.header, dt.data);
                },
                error: function(xhr, status, error) {
                    // Handle error during the kNN execution
                    console.log('Error:', error);
                    console.log('XHR object:', xhr);
                    console.log('Status:', status);
                    // Hide loading button
                    $('#loadUnclassifiedDatasetBtn').hide();
                    
                    // Display specific error message from the server response
                    const response = JSON.parse(xhr.responseText);
                    const message = response && response.message ? response.message : 'An unexpected error occurred.';

                    showAlert('danger', 'An error occurred while loading the dataset.', '#alertPreview');
                }
            });
        }
    });

    // Function to filter and display numerical features with no missing values
    function filterAndDisplayNumericalFeatures() {
        if (!dt.header.length || !dt.data.length) {
            return; // No data to filter
        }

        const header = dt.header;
        const data = dt.data;

        // Determine which columns are numerical and have no missing values
        let numericalFeatures = header.filter((feature, index) => {
            // Check if the column is numerical and has no missing values
            const columnValues = data.map(row => row[index]);
            const allValuesPresent = columnValues.every(value => value !== '');
            const isNumerical = columnValues.every(value => !isNaN(value) && value !== '');

            return isNumerical && allValuesPresent;
        });

        // Generate form HTML for numerical features with no missing values
        let formHtml = '';
        numericalFeatures.forEach(feature => {
            formHtml += `
                <div class="form-check form-check-inline">
                    <input class="form-check-input feature-checkbox" type="checkbox" value="${feature}" id="${feature}">
                    <label class="form-check-label" for="${feature}">
                        ${feature}
                    </label>
                </div>
            `;
        });
    }

    // Function to populate the table with dataset contents
    function populateTable(header, data) {
        var thead = $('#dataUnclassifiedFeaturesNamesTHead');
        var tbody = $('#dataUnclassifiedFeaturesNamesTBody');
        
        // Clear previous contents
        thead.empty();
        tbody.empty();
        
        // Add header row
        header.forEach(function(col) {
            thead.append('<th>' + col + '</th>');
        });
        
        // Limit the data to the first 10 rows
        var limitedData = data.slice(0, 10);
        
        // Add data rows
        limitedData.forEach(function(row) {
            var tr = '<tr>';
            row.forEach(function(cell) {
                tr += '<td>' + cell + '</td>';
            });
            tr += '</tr>';
            tbody.append(tr);
        });
    }

    // Handle event when download button is pressed
    $('#dnloadUnclassifiedBtn').on('click', function() {
        $('#dnloadUnclassifiedBtn').hide();
        $('#loadDnloadUnclassifiedBtn').show();

        // API call for downloading the unclassified dataset
        window.location.href = '../server/php/api/download_unclassified_dataset.php?token=' + token + '&file=' + unclassified_file;

        $('#dnloadUnclassifiedBtn').show();
        $('#loadDnloadUnclassifiedBtn').hide();       
    });

    // Store the dataset to be deleted
    $('#delUnclassifiedBtn').on('click', function() {
        $('#loadDelUnclassifiedBtn').show();
        $('#delUnclassifiedBtn').hide();
    });

    // Handle delete confirmation
    $('#delUnclassifiedDtBtn').on('click', function() {
        $('#delUnclassifiedDtBtn').hide();
        $('#loadDelUnclassifiedDtBtn').show();
        
        // API call for deleting the unclassified dataset
        $.ajax({
            url: '../server/php/api/delete_unclassified_dataset.php',
            method: 'DELETE',
            data: JSON.stringify({
                token: token,
                file: unclassified_file,
            }),
            contentType: 'application/json',
            success: function(response) {
                $('#delUnclassifiedDtBtn').show();
                $('#loadDelUnclassifiedDtBtn').hide();
                $('#delUnclassifiedBtn').show();
                $('#loadDelUnclassifiedBtn').hide();
                $('#selectUnclassifiedDataset').val('default');
                $('#delUnclassifiedDtBtn').prop('disabled', true);
                $('#delUnclassifiedBtn').prop('disabled', true);
                $('#dnloadUnclassifiedBtn').prop('disabled', true)
                $('#alertPreview').html('');
                $('#tableUnclassifiedDt').hide();
                $('#classifiedDt').hide();
                
                showAlert('success', response.message, '#alertDelUnclassifiedModal');
                loadUnclassifiedDatasets();
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#delUnclassifiedDtBtn').show();
                $('#loadDelUnclassifiedDtBtn').hide();
                $('#delUnclassifiedBtn').show();
                $('#loadDelUnclassifiedBtn').hide();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                // Show error alert
                showAlert('danger', message, '#alertDelUnclassifiedModal');
            }
        });
    });

    // When I close the delete modal window
    $('#closeDelUnclassifiedModalBtn').on('click', function() {
        $('#delUnclassifiedDtBtn').prop('disabled', false);
        $('#loadDelUnclassifiedBtn').hide();
        $('#delUnclassifiedBtn').show();
        $('#selectUnclassifiedDataset').val('default');
        $('#tableUnclassifiedDt').hide();
    });

    // Store the classified dataset
    let classifiedDataset = '';

    // Handler when the Classify data button is clicked
    $('#classifyDataBtn').on('click', function() {
        $('#classifyDataBtn').hide();
        $('#loadClassifyDtBtn').show();

        // API call to classify the unclassified dataset based on the model
        $.ajax({
            url: '../server/php/api/classify_data.php',
            method: 'POST',
            data: JSON.stringify({
                token: token,
                features: model_features,
                class: model_class,
                file: unclassified_file,
                model: model_file
            }),
            dataType: 'json',
            contentType: 'application/json',
            success: function(response) {
                $('#classifyDataBtn').show();
                $('#loadClassifyDtBtn').hide();
                $('#alertClassifyDtModal').html(response.message);
                $('#classifiedDt').show();

                // Check if the classification was successful
                if (response.return_code === 0) {                    
                    classifiedDataset = response.classified_file.split('/').pop();

                    // Make another API call to load the classified dataset
                    $.ajax({
                        url: '../server/php/api/load_classified_dataset.php',
                        method: 'GET',
                        data: {
                            token: token,
                            file: classifiedDataset // Sending parameters in query string
                        },
                        contentType: 'application/json',
                        success: function(response) {
                            var header = response.header;
                            var data = response.data;

                            // Call the function to populate the table
                            populateClassifiedDataset(header, data);
                        },
                        error: function(xhr, status, error) {
                            // Handle error during the kNN execution
                            console.log('Error:', error);
                            console.log('XHR object:', xhr);
                            console.log('Status:', status);
                            $('#classifyDataBtn').show();
                            $('#loadClassifyDtBtn').hide();

                            // Display specific error message from the server response
                            const response = JSON.parse(xhr.responseText);
                            const message = response && response.message ? response.message : 'An unexpected error occurred.';

                            $('#alertClassifyDtModal').html(message);
                        }
                    });

                    // Initialize variable resutls
                    var results = response.results;

                    // Call functions to display results, if the results file contains "accuracy", "average_precision", "average_recall", "average_f1_score", "precision_per_label", "recall_per_label" and "f1_score_per_label"
                    if (
                        results.accuracy !== undefined &&
                        results.average_precision !== undefined &&
                        results.average_recall !== undefined &&
                        results.average_f1_score !== undefined &&
                        results.precision_per_label !== undefined &&
                        results.recall_per_label !== undefined &&
                        results.f1_score_per_label !== undefined
                    ) {
                        // All metrics are present, now display the results
                        displayResults(results);
                        
                        // Show the buttons to display tables
                        $('#showMetricsBtn').show();
                        $('#showAvgMetricsBtn').show();
                    } else {
                        // Hide the buttons if metrics are not present
                        $('#showMetricsBtn').hide();
                        $('#showAvgMetricsBtn').hide();
                        }
                }
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#classifyDataBtn').show();
                $('#loadClassifyDtBtn').hide();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertPreview');
            }
        });
    });

    // Function to populate the classified dataset table
    function populateClassifiedDataset(header, data) {
        var thead = $('#dataNormalClassifiedFeaturesNamesTHead');
        var tbody = $('#dataNormalClassifiedFeaturesNamesTBody');

        // Clear previous contents
        thead.empty();
        tbody.empty();

        // Add header row
        header.forEach(function(col) {
            thead.append('<th>' + col + '</th>');
        });

        // Limit the data to the first 10 rows
        var limitedData = data.slice(0, 10);

        // Add data rows
        limitedData.forEach(function(row) {
            var tr = '<tr>';
            row.forEach(function(cell) {
                tr += '<td>' + cell + '</td>';
            });
            tr += '</tr>';
            tbody.append(tr);
        });
    }

    // Function to display results in the metrics table
    function displayResults(results) {
        // Display metrics per class in the table
        let metricsTBody = $('#resultsMetricsTBody');
        metricsTBody.empty();

        if (results.labels && results.precision_per_label) {
            results.labels.forEach((label, index) => {
                let precision = results.precision_per_label[index];
                let recall = results.recall_per_label[index];
                let fscore = results.f1_score_per_label[index];

                metricsTBody.append(
                    `<tr>
                        <td>${label}</td>
                        <td>${precision.toFixed(2)}</td>
                        <td>${recall.toFixed(2)}</td>
                        <td>${fscore.toFixed(2)}</td>
                    </tr>`
                );
            });
        }

        // Display average metrics
        let avgMetricsTBody = $('#resultsAvgMetricsTBody'); // Make sure this matches your HTML ID
        avgMetricsTBody.empty();
        avgMetricsTBody.append(
            `<tr>
                <td>${results.accuracy.toFixed(2)}</td>
                <td>${results.average_precision.toFixed(2)}</td>
                <td>${results.average_recall.toFixed(2)}</td>
                <td>${results.average_f1_score.toFixed(2)}</td>
            </tr>`
        );
    }

    $('#exportBtn').on('click', function() {
        $('#exportBtn').hide();
        $('#loadExportBtn').show();

        // API call to download the classified dataset
        window.location.href = '../server/php/api/download_classified_dataset.php?token=' + token + '&file=' + classifiedDataset;

        $('#exportBtn').show();
        $('#loadExportBtn').hide();
    });
});
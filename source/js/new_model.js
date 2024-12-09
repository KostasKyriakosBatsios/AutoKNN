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

    // Hide loading buttons, initially
    $('#loadUploadDatasetBtn, #loadDelDtBtn, #loadDnloadBtn, #loadDelBtn, #loadDatasetBtn, #loadUploadingDatasetBtn, #loadBuildModelBtn, #loadSaveModelBtn').hide();
        
    // Hide the other windows except for the Datasets, initially
    $('#tableDt, #parameters, #dtProcessing, #modelEvaluation').hide();

    // Initialize all the selected parameters and options of the user, so no previous data is shown
    $('#selectDataset').html('');
    $('#selectClass').prop('selectedIndex', 0);
    $('#k').val('');
    $('#metricDistance').prop('selectedIndex', 0);

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            placement: 'right' // Place the tooltip to the right
        });
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
        localStorage.removeItem('allowPublic');
        window.location.href = '../index.html'; // Redirect to home page
    });

    // Function to display the alert message
    function showAlert(type, message, id) {
        var alertMessage = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $(id).html(alertMessage); // Display alert in the datasets section
    }

    function loadDatasets() {
        // API call for loading datasets
        $.ajax({
            url: '../server/php/api/list_datasets.php',
            method: 'GET',
            data: {
                token: token
            },
            success: function(response) {
                if (response.error) {
                    showAlert('danger', response.error, '#alertDatasets');
                    return;
                }
                
                var publicDatasets = response.public || [];
                var privateDatasets = response.private || [];
                var $selectDataset = $('#selectDataset');

                $selectDataset.empty(); // Clear any existing options
                $selectDataset.append('<option value="default" selected>Select dataset</option>');
                
                publicDatasets.forEach(function(dataset) {
                    $selectDataset.append(`<option value="${dataset.path}" data-type="public">[Public folder] ${dataset.name}</option>`);
                });

                privateDatasets.forEach(function(dataset) {
                    $selectDataset.append(`<option value="${dataset.path}" data-type="private">[Private folder] ${dataset.name}</option>`);
                });
            },
            error: function(error, xhr, status) {
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';
                
                showAlert('danger', message, '#alertDatasets');
            }
        });
    }

    // Function to load the datasets
    loadDatasets();

    $('#uploadBtn').on('click', function() {
        $('#loadUploadDatasetBtn').show();
    });

    // Event handler for the Upload modal close button
    $('#closeUploadModalBtn').on('click', function () {
        // Clear file input
        $('#inputGroupFile').val('');

        // Reset folder selection to default
        $('#selectFolder').val('default');

        // Clear any existing alerts in the modal
        $('#alertUploadModal').html('');

        // Reset the upload and loading buttons
        $('#uploadDtBtn').show();
        $('#loadUploadingDatasetBtn').hide();
        $('#loadUploadDatasetBtn').hide();
    });
    
    // When the x button is pressed at upload modal window
    $('#closeXBtn').on('click', function() {
        $('#inputGroupFile').val('');

        $('#selectFolder').val('default');

        $('#alertUploadModal').html('');

        $('#uploadDtBtn').show();
        $('#loadUploadingDatasetBtn').hide();
        $('#loadUploadDatasetBtn').hide();
    });

    // Retrieve allowPublic from localStorage
    var allowPublic = parseInt(localStorage.getItem('allowPublic'), 10);

    function validateUpload() {
        var fileInput = $('#inputGroupFile')[0].files[0];
        var folderSelected = $('#selectFolder').val();
        var folderValid = folderSelected !== 'default';
        var allowUpload = allowPublic || folderSelected === 'private';

        // Validate file size
        if (fileInput && fileInput.size > 10 * 1024 * 1024) {
            showAlert('danger', 'File size exceeds 10MB limit.', '#alertUploadModal');
            this.value = '';
            fileInput = null
            return false;
        }

        // Validate file type
        var allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
        if (fileInput && !allowedTypes.includes(fileInput.type)) {
            showAlert('danger', 'Invalid file type. Only CSV files are allowed.', '#alertUploadModal');
            this.value = '';
            fileInput = null
            return false;
        }

        // Check if the user is allowed to upload to the public folder
        if (folderSelected === 'public' && !allowPublic) {
            showAlert('danger', 'You are not allowed to upload to the public folder.', '#alertUploadModal');
            return false;
        }

        $('#uploadDtBtn').prop('disabled', !(fileInput && folderValid && allowUpload));
        return true;
    }

    $('#inputGroupFile, #selectFolder').on('change', validateUpload);

    // Handle the file upload
    $('#uploadDtBtn').on('click', function() {
        // Hide the upload button and show the loading button
        $('#uploadDtBtn').hide();
        $('#loadUploadingDatasetBtn').show();
        
        if (!validateUpload()) {
            return;
        }

        var fileInput = $('#inputGroupFile')[0].files[0];
        var folder = $('#selectFolder').val();

        var formData = new FormData();
        formData.append('file', fileInput);
        formData.append('folder', folder);
        formData.append('token', token);
        formData.append('allowPublic', allowPublic);

        // API call for uploading a dataset
        $.ajax({
            url: '../server/php/api/upload_dataset.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Show the upload button and hide the loading button
                $('#uploadDtBtn').show();
                $('#loadUploadingDatasetBtn').hide();
                $('#loadUploadDatasetBtn').hide();
                showAlert('success', response.message, '#alertUploadModal');
                loadDatasets();

                // Making sure when the upload was successful and the select folder is default, to not show any other windows
                $('#alertPreview').html('');
                $('#parameters').hide();
                $('#individualFeatures').empty();
            },
            error: function(error, xhr, status) {
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                // Show the upload button and hide the loading button
                $('#uploadDtBtn').show();
                $('#loadUploadingDatasetBtn').hide();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertUploadModal');
            }
        });
    });

    // Global variables: chosen file, type of folder, the selected dataset and the data counter (used later to determine the stratified sampling option)
    let selectedOption = null;
    let file = '';
    let folderType = '';
    let dt = {
        header: [],
        data: []
    };
    let dataCounter = 0;

    // Event listener for dataset selection
    $('#selectDataset').on('change', function() {
        $('#alertPreview').html('');
        $('#parameters').hide();
        $('#individualFeatures').empty();

        selectedOption = $('#selectDataset option:selected');
        file = selectedOption.val().split('/').pop();
        folderType = selectedOption.data('type');

        if (file && file !== 'default') {
            $('#dnloadBtn').prop('disabled', false);
            $('#delBtn').prop('disabled', false);
            $('#tableDt').show();
            $('#parameters').show();
            $('#dtProcessing').hide();
            $('#modelEvaluation').hide();
            $('#selectAllFeatures').prop('checked', true);
            $('#selectClass').prop('selectedIndex', 0);
            $('#k').val('');
            $('#metricDistance').prop('selectedIndex', 0);
            $('#checkAutoK').prop('checked', true);
        } else {
            $('#dnloadBtn').prop('disabled', false);
            $('#delBtn').prop('disabled', false);
            $('#tableDt').hide();
            $('#parameters').hide();
            $('#dtProcessing').hide();
            $('#modelEvaluation').hide();
            $('#selectAllFeatures').prop('checked', false);
            $('#selectClass').prop('selectedIndex', 0);
            $('#k').val('');
            $('#metricDistance').prop('selectedIndex', 0);
            $('#checkAutoK').prop('checked', false);
        }

        if (file && token) {
            $('#loadDatasetBtn').show();

            // API call for loading the chosen dataset's contents
            $.ajax({
                url: '../server/php/api/load_dataset.php',
                method: 'GET',
                data: {
                    token: token,
                    file: file,
                    folder: folderType
                },
                success: function(response) {
                    // Hide loading button
                    $('#loadDatasetBtn').hide();
                
                    if (response.message) {
                        // Show alert if there's a message
                        showAlert('danger', response.message, '#alertPreview');
                        return;
                    }
                
                    dataCounter = response.counter;
                
                    // Show or hide stratified sampling based on dataCounter
                    if (dataCounter < 1000) {
                        $('#stratify').hide();
                        $('#checkStratifiedSampling').prop('checked', false);
                    } else {
                        $('#stratify').show();
                        $('#checkStratifiedSampling').prop('checked', true);
                    }
                
                    dt = {
                        header: response.header,
                        data: response.data
                    };
                
                    // Filter and display numerical features with no missing values, and display classes
                    filterAndDisplayNumericalFeatures();
                    populateClassesSelect();
                
                    // Populate table with dataset contents
                    populateTable(dt.header, dt.data);
                
                    $('#parameters').show();
                
                    // Update global variables after the UI has been updated
                    updateSelectedFeatures();
                    updateSelectedClass();
                
                    // Handle parameter settings after all relevant data is loaded
                    handleParameterSettings();
                },
                error: function(error, xhr, status) {
                    console.log('Error starting the algorithm:', error);
                    console.log('XHR object:', xhr);
                    console.log('Status:', status);
                    // Hide loading button
                    $('#loadDatasetBtn').hide();

                    // Display specific error message from the server response
                    const response = xhr.responseJSON;
                    const message = response && response.message ? response.message : 'An unexpected error occurred.';

                    showAlert('danger', message, '#alertPreview');
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
                    <input class="form-check-input feature-checkbox" type="checkbox" value="${feature}" id="${feature}" checked>
                    <label class="form-check-label" for="${feature}">
                        ${feature}
                    </label>
                </div>
            `;
        });

        $('#individualFeatures').html(formHtml);
    }

    // Function to populate the class options in the #selectClass dropdown
    function populateClassesSelect() {
        if (!dt.header.length || !dt.data.length) {
            return; 
        }

        const header = dt.header;
        const data = dt.data;

        // Determine which columns have no missing values
        let featuresWithNoMissingValues = header.filter((feature, index) => {
            const columnValues = data.map(row => row[index]);
            return columnValues.every(value => value !== ''); // All values are present
        });

        // Basic heuristic to determine the class column (assuming categorical and non-numeric values are classes)
        let potentialClassFeatures = featuresWithNoMissingValues.filter(feature => {
            const columnValues = data.map(row => row[header.indexOf(feature)]);
            // Check if the column values are categorical (string values)
            return columnValues.some(value => isNaN(value));
        });

        // If no clear class feature is found, use the first one from the non-missing features
        if (potentialClassFeatures.length === 0) {
            potentialClassFeatures = featuresWithNoMissingValues;
        }

        // Populate #selectClass with these potential class features
        let classOptions = '<option value="default" selected>Select a class</option>';
        potentialClassFeatures.forEach(feature => {
            classOptions += `<option value="${feature}">${feature}</option>`;
        });

        $('#selectClass').html(classOptions);
    }     

    // Function to populate the table with dataset contents
    function populateTable(header, data) {
        var thead = $('#dataFeaturesNamesTHead');
        var tbody = $('#dataFeaturesNamesTBody');
        
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
    $('#dnloadBtn').on('click', function() {
        $('#dnloadBtn').hide();
        $('#loadDnloadBtn').show();

        // API call for downloading the dataset
        window.location.href = '../server/php/api/download_dataset.php?token=' + token + '&file=' + file;

        $('#dnloadBtn').show();
        $('#loadDnloadBtn').hide();       
    });

    // Store the dataset to be deleted
    $('#delBtn').on('click', function() {
        $('#loadDelBtn').show();
        $('#delBtn').hide();
    });

    // Handle delete confirmation
    $('#delDtBtn').on('click', function() {
        $('#delDtBtn').hide();
        $('#loadDelDtBtn').show();
        
        // API call for deleting the dataset
        $.ajax({
            url: '../server/php/api/delete_dataset.php',
            method: 'DELETE',
            data: JSON.stringify({
                token: token,
                file: file,
                folder: folderType
            }),
            contentType: 'application/json',
            success: function(response) {
                $('#delDtBtn').show();
                $('#loadDelDtBtn').hide();
                $('#delBtn').show();
                $('#loadDelBtn').hide();
                $('#selectDataset').val('default');
                $('#delDtBtn').prop('disabled', true);
                $('#delBtn').prop('disabled', true);
                $('#dnloadBtn').prop('disabled', true)
                $('#alertPreview').html('');
                $('#tableDt').hide();
                $('#normal').hide();                
                
                showAlert('success', response.message, '#alertDelModal');
                loadDatasets();
            },
            error: function(error, xhr, status) {
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#delDtBtn').show();
                $('#loadDelDtBtn').hide();
                $('#delBtn').show();
                $('#loadDelBtn').hide();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';
                
                showAlert('danger', message, '#alertDelModal');
            }
        });
    });

    // When I close the delete modal window
    $('#closeDelModalBtn').on('click', function() {
        $('#delDtBtn').prop('disabled', false);
        $('#loadDelBtn').hide();
        $('#delBtn').show();
        $('#alertDelModal').html('');
    });

    // Global variables for the kNN algorithm
    let selectedFeatures = [];
    let selectedClass = '';
    let k = null;
    let metricDistance = null;
    let p = null;
    let autoK = false;
    let stratifiedSampling = false;

    // Function to update the global variable
    function updateSelectedFeatures() {
        selectedFeatures = []; // Clear the array

        // Loop through each checkbox to see if it's checked
        $('#individualFeatures .feature-checkbox:checked').each(function() {
            selectedFeatures.push($(this).val()); // Add checked feature to the array
        });
    }

    // Handle event when the "Select All" checkbox is pressed
    $('#selectAllFeatures').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#individualFeatures .feature-checkbox').prop('checked', isChecked);
        updateSelectedFeatures();
    });

    // Handle individual feature checkbox click events
    $('#individualFeatures').on('change', '.feature-checkbox', function() {
        const allChecked = $('#individualFeatures .feature-checkbox').length === $('#individualFeatures .feature-checkbox:checked').length;
        $('#selectAllFeatures').prop('checked', allChecked);
        updateSelectedFeatures();
    });

    // Function to save the user's choice from the selectClass dropdown
    function updateSelectedClass() {
        selectedClass = $('#selectClass').val();
    }

    // Event listener for the selectClass dropdown change
    $('#selectClass').on('change', function() {
        updateSelectedClass(); // Update the global variable
    });

    // Function to handle enabling/disabling of the fields based on conditions
    function handleParameterSettings() {
        autoK = $('#checkAutoK').is(':checked');
        var metricDistanceValue = $('#metricDistance').val();

        // Set metricDistance and p based on selected option
        switch (metricDistanceValue) {
            case 'minkowski3':
                metricDistance = 'minkowski';
                p = 3;
                break;
            case 'minkowski4':
                metricDistance = 'minkowski';
                p = 4;
                break;
            case 'autoDistance':
                metricDistance = ['euclidean', 'manhattan', 'chebyshev', 'minkowski'];
                p = [3, 4];
                break;
            case 'euclidean':
            case 'manhattan':
            case 'chebyshev':
                metricDistance = metricDistanceValue;
                p = null;
                break;
            default:
                metricDistance = 'default';
                p = null;
        }

        if (autoK) {
            $('#k').prop('disabled', true);
            k = [1,3,5,7,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49];
        } else {
            $('#k').prop('disabled', false);
            k = parseInt($('#k').val(), 10);
        }

        // Save stratified sampling choice
        stratifiedSampling = $('#checkStratifiedSampling').is(':checked');
    }

    $('#metricDistance, #k, #checkAutoK').on('change', function() {
        handleParameterSettings();
    });

    $('#checkStratifiedSampling').on('change', function() {
        handleParameterSettings();
    });

    // Checking the validity of the inputs to ensure the building model will be proper
    function validateInputs() {
        $('#alertBuildModel').html('');

        if (selectedFeatures.length === 0) {
            showAlert('warning', 'You need to select features.', '#alertBuildModel');
            return false;
        }
    
        if (selectedClass === 'default' || selectedClass.trim() === '') {
            showAlert('warning', 'You need to select a class.', '#alertBuildModel');
            return false;
        }
    
        const validK = (Array.isArray(k)) || (!isNaN(k) && k >= 1 && k <= 50);
        if (!validK) {
            showAlert('warning', 'You need to enter a valid value for k (between 1 and 50 or a valid range).', '#alertBuildModel');
            return false;
        }
    
        const validDistances = ['euclidean', 'manhattan', 'chebyshev', 'minkowski'];
        const isValidDistance = (typeof metricDistance === 'string' && validDistances.includes(metricDistance)) || 
                                (Array.isArray(metricDistance) && metricDistance.every(distance => validDistances.includes(distance)));
        if (!isValidDistance) {
            showAlert('warning', 'You need to select a valid metric distance.', '#alertBuildModel');
            return false;
        }
        
        const isMinkowski = metricDistance === 'minkowski' || (Array.isArray(metricDistance) && metricDistance.includes('minkowski'));
        const validP = (Array.isArray(p) && p.length === 2 && p.includes(3) && p.includes(4)) || p === 3 || p === 4;
        if (isMinkowski && (!p || !validP)) {
            showAlert('warning', 'You need to enter a valid value for p (3 or 4, or both [3, 4]) when using Minkowski distance.', '#alertBuildModel');
            return false;
        }
    
        return true;
    }

    // Global variables making them into an array form to pass them smoothly
    let features = [];
    let k_value = [];
    let metricDistance_value = [];
    let p_value = [] || null;   

    // Initialize dataset_id and the folder of the results
    let datasetId = null;
    let resultsFolder = '';

    // Handler when the user presses the build model button
    $('#buildModelBtn').on('click', function() {
        $('#buildModelBtn').prop('disabled', true);
        $('#loadBuildModelBtn').show();

        // Check if all inputs are valid
        if (validateInputs()) {
            // Always pass these as arrays, even if there's only one element (also check if p is null, if it's not, turn it into an array form)
            features = Array.isArray(selectedFeatures) ? selectedFeatures : [selectedFeatures];
            k_value = Array.isArray(k) ? k : [k];
            metricDistance_value = Array.isArray(metricDistance) ? metricDistance : [metricDistance];
            
            if (p !== null) {
                p_value = Array.isArray(p) ? p : [p];
            }

            $('#loadBuildModelBtn').hide();
            $('#dtProcessing').show();
            $('#datasetName').text(file);
            $('#processStatus').text('In progress');
            
            showAlert('warning', 'Parameters are valid. Wait until the status changes to "Completed".', '#alertBuildModel');

            executeAlgorithm();
            $('#loadBuildModelBtn').hide();
        } else {
            $('#buildModelBtn').prop('disabled', false);
            $('#loadBuildModelBtn').hide();
        }
    });

    // Function to execute the kNN algorithm
    function executeAlgorithm() {
        // API call to execute the kNN algorithm
        $.ajax({
            url: '../server/php/api/post_knn_train_test.php',
            method: 'POST',
            data: JSON.stringify({
                token: token,
                file: file,
                folder: folderType,
                features: features,
                target: selectedClass,
                k_value: k_value,
                distance_value: metricDistance_value,
                p_value: p_value,
                stratify: stratifiedSampling
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.message === "Completed execution of algorithm" || response.message === "Algorithm already executed") {
                    // Fetch and display the results immediately after execution
                    $('#buildModelBtn').prop('disabled', false);
                    $('#processStatus').text(response.status);
                    datasetId = response.dataset_id;
                    resultsFolder = response.folder;
                    fetchResults(datasetId);
                } else {
                    // Show warning if something went wrong
                    $('#buildModelBtn').prop('disabled', false);
                    $('#processStatus').text(response.status);
                    showAlert('warning', response.message, '#alertBuildModel');
                }
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                $('#buildModelBtn').prop('disabled', false);
                $('#loadBuildModelBtn').hide();
                showAlert('danger', message, '#alertBuildModel');
                $('#processStatus').text(response.status);
            }
        });
    }

    // Function to fetch and display the results
    function fetchResults(datasetId) {
        // API call on retrieving the results from the kNN algorithm
        $.ajax({
            url: '../server/php/api/get_knn_train_test.php',
            method: 'GET',
            data: {
                token: token,
                dataset_id: datasetId,
                folder: resultsFolder
            },
            contentType: 'application/json',
            success: function(results) {
                if (results) {
                    $('#processStatus').text('Completed');
                    $('#evaluateBtn').prop('disabled', false);
                    $('#saveModelBtn').prop('disabled', false);
                    showAlert('success', 'Model built successfully. Results fetched.', '#alertBuildModel');
                    displayResults(results);
                } else {
                    showAlert('danger', 'No results found. Try re-running the algorithm.', '#alertBuildModel');
                }
            },
            error: function(error, xhr, status) {
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertBuildModel');
            }
        });
    }

    // Function to display the results
    function displayResults(results) {
        best_k = results.best_k;
        best_distance = results.best_distance;
        best_p = results.best_p;

        // Display metrics per class
        let metricsTBody = $('#resultsMetricsTBody');
        metricsTBody.empty();
        results.class_metrics.forEach(metric => {
            metricsTBody.append(
                `<tr>
                    <td>${metric.class}</td>
                    <td>${metric.precision.toFixed(2)}</td>
                    <td>${metric.recall.toFixed(2)}</td>
                    <td>${metric.f1.toFixed(2)}</td>
                </tr>`
            );
        });

        // Display average metrics
        let avgMetricsTBody = $('#resultsavgMetricsTBody');
        avgMetricsTBody.empty();
        avgMetricsTBody.append(
            `<tr>
                <td>${results.max_accuracy.toFixed(2)}</td>
                <td>${results.average_precision.toFixed(2)}</td>
                <td>${results.average_recall.toFixed(2)}</td>
                <td>${results.average_f1.toFixed(2)}</td>
            </tr>`
        );

        // Display best parameters
        let bestParmsTBody = $('#resultsBestParmsTBody');
        bestParmsTBody.empty();

        if (best_p === null) {
            bestParmsTBody.append(
                `<tr>
                    <td>${best_k}</td>
                    <td>${best_distance}</td>
                </tr>`
            );
        } else {
            bestParmsTBody.append(
                `<tr>
                    <td>${best_k}</td>
                    <td>${best_distance} (p=${best_p})</td>
                </tr>`
            );
        }
    }
    
    // When pressed the button, make the model evaluation window appear
    $('#evaluateBtn').on('click', function() {
        var evaluationDiv = document.getElementById('modelEvaluation');
        var btn = this;

        if (evaluationDiv.style.display === 'none') {
            evaluationDiv.style.display = 'block';
            btn.innerHTML = '<i><ion-icon name="eye-off-outline"></ion-icon></i><span> Hide Evaluation</span>';
        } else {
            evaluationDiv.style.display = 'none';
            btn.innerHTML = '<i><ion-icon name="eye-outline"></ion-icon></i><span> Show Evaluation</span>';
        }
    });

    // Save model event listener
    $('#saveModelBtn').on('click', function() {
        $('#saveModelBtn').hide();
        $('#loadSaveModelBtn').show();
    
        var modelName = $('#modelName').val().trim();
    
        // Ensure the model name contains at least one letter and optionally a number
        if (!modelName || !/^(?=.*[a-zA-Z])[a-zA-Z0-9]*$/.test(modelName)) {
            showAlert('danger', 'Please enter a valid model name containing at least one letter, and optionally a number.', '#alertEvaluation');
            $('#saveModelBtn').show();
            $('#loadSaveModelBtn').hide();
            return;
        }
    
        var requestData = JSON.stringify({
            token: token,
            file: file,
            dataset_id: datasetId,
            folder: resultsFolder,
            features: features,
            target: selectedClass,
            k_value: k_value,
            distance_value: metricDistance_value,
            p_value: p,
            best_k_value: best_k,
            best_distance_value: best_distance,
            best_p_value: best_p,
            stratify: stratifiedSampling,
            model_name: modelName
        });
    
        // API call to save the model
        $.ajax({
            url: '../server/php/api/save_model.php',
            method: 'POST',
            data: requestData,
            contentType: 'application/json',
            success: function() {
                $('#saveModelBtn').show();
                $('#loadSaveModelBtn').hide();
                showAlert('success', 'Model saved successfully.', '#alertEvaluation');
            },
            error: function(error, xhr, status) {
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#saveModelBtn').show();
                $('#loadSaveModelBtn').hide();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertEvaluation');
            }
        });
    });
});
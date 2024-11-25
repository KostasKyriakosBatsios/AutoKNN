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

    // Initialize button as hiden
    $('#loadSaveModelBtn').hide();

    // Initialize alert messages, texts, ...
    $('#datasetName').text('None');
    $('#processStatus').text('No status');

    // Get parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    let file = urlParams.get('file');
    let folder = urlParams.get('folder');
    let selectedFeatures = urlParams.get('features');
    let selectedClass = urlParams.get('target');
    let k_value = urlParams.get('k_value');
    let metricDistance_value = urlParams.get('distance_value');
    let p = urlParams.get('p_value');
    let stratifiedSampling = urlParams.get('stratify');

    // Global variables making them into an array form to pass them smoothly
    let features = [];
    let k = [];
    let metricDistance = [];

    // Always pass these as arrays, even if there's only one element
    features = Array.isArray(selectedFeatures) ? selectedFeatures : [selectedFeatures];
    k = Array.isArray(k_value) ? k_value : [k_value];
    metricDistance = Array.isArray(metricDistance_value) ? metricDistance_value : [metricDistance_value];

    // Debug values of parameters
    console.log('File:', file);
    console.log('Folder:', folder);
    console.log('Features:', features);
    console.log('Class:', selectedClass);
    console.log('k:', k);
    console.log('Metric distance:', metricDistance);
    console.log('p:', p);
    console.log('Stratified sampling:', stratifiedSampling);

    // Initialize dataset's id
    let datasetId = null;

    // Make dataset's name and status visible, if all variables have passed properly
    if (file && folder && selectedClass && features && k && metricDistance && p && stratifiedSampling) {
        $('#datasetName').text(file);
        $('#processStatus').text('In progress');

        var requestData = JSON.stringify({
            token: token,
            file: file,
            folder: folder,
            features: features,
            target: selectedClass,
            k_value: k,
            distance_value: metricDistance,
            p_value: p,
            stratify: stratifiedSampling
        });

        // Save the parameters and the status to local storage so the process will not get lost
        localStorage.setItem('file', file);
        localStorage.setItem('folder', folder);
        localStorage.setItem('features', features);
        localStorage.setItem('target', selectedClass);
        localStorage.setItem('k_value', k);
        localStorage.setItem('distance_value', metricDistance);
        localStorage.setItem('p_value', p);
        localStorage.setItem('stratify', stratifiedSampling);

        // API call to execute the kNN algorithm
        $.ajax({
            url: '../server/php/api/post_knn_train_test.php',
            method: 'POST',
            data: requestData,
            contentType: 'application/json',
            success: function(response) {
                if (response.message === "Completed execution of algorithm") {
                    // Fetch and display the results immediately after execution
                    $('#buildModelBtn').prop('disabled', false);
                    $('#processStatus').text(response.status);
                    datasetId = response.dataset_id;
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
                $('#buildModelBtn').prop('disabled', false);
                $('#loadBuildModelBtn').hide();
                showAlert('danger', 'Failed to start the algorithm.', '#alertBuildModel');
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
                dataset_id: datasetId,
                token: token,
                folder: folder
            },
            contentType: 'application/json',
            success: function(results) {
                displayResults(results);
                $('#evaluateBtn').prop('disabled', false);
                $('#saveModelBtn').prop('disabled', false);
            },
            error: function() {
                showAlert('danger', 'Failed to retrieve the results.', '#alertBuildModel');
            }
        });
    }

    // Global variables to save the best k, metric distance and p
    let best_k = null;
    let best_distance = null;
    let best_p = null;

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
                <td>${results.average_accuracy.toFixed(2)}</td>
                <td>${results.average_precision.toFixed(2)}</td>
                <td>${results.average_recall.toFixed(2)}</td>
                <td>${results.average_f1.toFixed(2)}</td>
            </tr>`
        );
    
        // Display best parameters
        let bestParmsTBody = $('#resultsBestParmsTBody');
        bestParmsTBody.empty();
        bestParmsTBody.append(
            `<tr>
                <td>${results.max_accuracy.toFixed(2)}</td>
                <td>${results.best_k}</td>
                <td>${results.best_distance}</td>
                <td>${results.best_precision.toFixed(2)}</td>
                <td>${results.best_recall.toFixed(2)}</td>
                <td>${results.best_f1.toFixed(2)}</td>
                <td>${results.best_p || 'None'}</td>
            </tr>`
        );
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
    
        // Ensure the model name starts with "model_" and contains at least one letter afterward
        if (!modelName || !/^model_[a-zA-Z].*[0-9a-zA-Z]*$/.test(modelName)) {
            showAlert('danger', 'Please enter a valid model name starting with "model_" and containing at least one letter.', '#alertEvaluation');
            $('#saveModelBtn').show();
            $('#loadSaveModelBtn').hide();
            return;
        }
    
        var requestData = JSON.stringify({
            token: token,
            file: file,
            folder: folder,
            features: features,
            target: selectedClass,
            k_value: best_k,
            distance_value: best_distance,
            p_value: best_p,
            stratify: stratifiedSampling,
            dataset_id: datasetId,
            model_name: modelName
        });
    
        // API call to save the model
        $.ajax({
            url: '../server/php/api/save_model.php',
            method: 'POST',
            data: requestData,
            contentType: 'application/json',
            success: function(response) {
                $('#saveModelBtn').show();
                $('#loadSaveModelBtn').hide();
                showAlert('success', 'Model saved successfully.', '#alertEvaluation');
                initializePage();
            },
            error: function(error) {
                $('#saveModelBtn').show();
                $('#loadSaveModelBtn').hide();
                showAlert('danger', 'Failed to save the model.', '#alertEvaluation');
            }
        });
    });
});
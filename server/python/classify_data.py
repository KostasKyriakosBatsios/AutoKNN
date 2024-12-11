import sys
sys.path.insert(0, '/var/www/html/webkmeans/kclusterhub/autoknn/.venv/lib/python3.11/site-packages')
import pandas as pd
import joblib
import json
from sklearn import metrics

if len(sys.argv) != 6:
    print("Usage: python classify_data.py <file_path> <model_path> <features> <class_column> <saved_file_path>")
    sys.exit(1)

# Extract arguments passed from PHP
file_path = sys.argv[1]
model_path = sys.argv[2]
features = sys.argv[3].split(',')
class_name = sys.argv[4]
saved_file_path = sys.argv[5]

# Load the dataset
dataset = pd.read_csv(file_path)
attributes = dataset[features]

# Load the model and make predictions
model = joblib.load(model_path)
predicted_values = model.predict(attributes)

# Initialize results
results = {}

# Check if the class column is provided and valid
if class_name != 'None' and class_name in dataset.columns:
    # Calculate metrics
    class_label = dataset[class_name]
    labels = class_label.unique().tolist()  # Get unique labels

    # Calculate accuracy
    accuracy = round(metrics.accuracy_score(class_label, predicted_values), 2)

    # Calculate precision, recall, and f1-score per label
    precision_per_label, recall_per_label, fscore_per_label, _ = metrics.precision_recall_fscore_support(
        class_label, predicted_values, average=None, labels=labels, zero_division=0
    )
    precision_per_label = [round(p, 2) for p in precision_per_label]
    recall_per_label = [round(r, 2) for r in recall_per_label]
    fscore_per_label = [round(f, 2) for f in fscore_per_label]

    # Calculate average precision, recall, and f1-score
    average_precision, average_recall, average_fscore, _ = metrics.precision_recall_fscore_support(
        class_label, predicted_values, average='macro', zero_division=0
    )
    average_precision, average_recall, average_fscore = (
        round(average_precision, 2),
        round(average_recall, 2),
        round(average_fscore, 2),
    )

    # Add predictions to the dataset
    dataset["predicted"] = predicted_values

    # Prepare output data
    columns = dataset[features + ["predicted"]].columns.to_list()
    rows = dataset[features + ["predicted"]].values.tolist()
    data = [columns] + rows

    # Construct the results dictionary
    results = {
        "dataset": data,
        "accuracy": accuracy,
        "average_precision": average_precision,
        "average_recall": average_recall,
        "average_f1_score": average_fscore,
        "precision_per_label": precision_per_label,
        "recall_per_label": recall_per_label,
        "f1_score_per_label": fscore_per_label,
        "labels": labels,
    }
else:
    # Add predictions to the dataset
    dataset["predicted"] = predicted_values

    # Prepare output data
    columns = dataset[features + ["predicted"]].columns.to_list()
    rows = dataset[features + ["predicted"]].values.tolist()
    data = [columns] + rows

    # Construct the results dictionary
    results = {
        "dataset": data,
        "labels": list(predicted_values),  # Predicted labels as a list
    }

# Save the classified dataset
dataset.to_csv(saved_file_path, index=False, encoding='utf-8')

print(json.dumps(results))
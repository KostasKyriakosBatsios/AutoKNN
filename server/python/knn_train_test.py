import sys
import json
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, classification_report

def evaluate_knn(X_train, y_train, X_test, y_test, k, distance, p=None):
    clf = KNeighborsClassifier(n_neighbors=k, metric=distance, p=p)
    clf.fit(X_train, y_train)
    y_pred = clf.predict(X_test)

    # Overall metrics
    accuracy = accuracy_score(y_test, y_pred)
    precision = precision_score(y_test, y_pred, average='weighted', zero_division=0)
    recall = recall_score(y_test, y_pred, average='weighted', zero_division=0)
    f1 = f1_score(y_test, y_pred, average='weighted', zero_division=0)

    # Class-wise metrics
    class_report = classification_report(y_test, y_pred, output_dict=True)

    return accuracy, precision, recall, f1, class_report


if len(sys.argv) != 9:
    print("Usage: python knn_train_test.py <normalized_file> <features> <target> <k_values> <distances> <p_value> <stratified_sampling> <results_file_path>")
    sys.exit(1)

# The script arguments passed from PHP
normalized_file = sys.argv[1]
features = sys.argv[2].split(",")  # Comma-separated string -> list
target = sys.argv[3]
k_values = [int(k) for k in sys.argv[4].split(",")]  # Convert to list of integers
distance_values = sys.argv[5].split(",")  # Comma-separated string -> list

p_value = sys.argv[6] if sys.argv[6] and sys.argv[6] != 'null' else None
if p_value:
    p_value = int(p_value)  # Convert to integer if it's not None

stratified_sampling = sys.argv[7].lower() == 'true'  # Convert to boolean
results_path = sys.argv[8]

# Load dataset
dataset = pd.read_csv(normalized_file)

# Split the features and target variables
X = dataset[features].values
y = dataset[target].values

# Perform train test split (70% train, 30% test)
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42)

num_classes = len(set(y_train))

# Check if stratified sampling is needed
if stratified_sampling:
    # Calculate the maximum number of samples to use
    if len(X_train) > 1000:
        train_size = 1000
    else:
        train_size = len(X_train) - num_classes  # Leave at least one sample per class for the test set

    # Perform stratified sampling on the training set
    X_sampled, _, y_sampled, _ = train_test_split(X_train, y_train, train_size=train_size, random_state=42, stratify=y_train)
else:
    X_sampled, y_sampled = X_train, y_train

# Initialize variables for storing best metrics and parameters
max_accuracy = 0
best_k = 0
best_p = None
best_distance = ''
best_precision = 0
best_recall = 0
best_f1 = 0

# Variables to calculate the average metrics
average_accuracy = 0
average_precision = 0
average_recall = 0
average_f1 = 0
total_evaluations = 0

# Store per-label metrics
label_metrics = {label: {'precision': 0, 'recall': 0, 'f1': 0, 'count': 0} for label in set(y_test)}

# Evaluate different combinations
for k in k_values:
    for distance in distance_values:
        if distance == 'minkowski' and p_value:
            accuracy, precision, recall, f1, class_report = evaluate_knn(X_sampled, y_sampled, X_test, y_test, k, distance, p_value)
        else:
            accuracy, precision, recall, f1, class_report = evaluate_knn(X_sampled, y_sampled, X_test, y_test, k, distance)

        if accuracy > max_accuracy:
            max_accuracy = accuracy
            best_k = k
            best_distance = distance
            best_p = p_value if distance == 'minkowski' else None
            best_precision = precision
            best_recall = recall
            best_f1 = f1
            best_class_metrics = class_report
        
        # Accumulate average metrics
        average_accuracy += accuracy
        average_precision += precision
        average_recall += recall
        average_f1 += f1
        total_evaluations += 1

        # Accumulate per-label metrics
        for label, metrics in class_report.items():
            if label not in ['accuracy', 'macro avg', 'weighted avg']:
                label_metrics[label]['precision'] += metrics['precision']
                label_metrics[label]['recall'] += metrics['recall']
                label_metrics[label]['f1'] += metrics['f1-score']
                label_metrics[label]['count'] += 1

# Finalize average metrics
average_accuracy /= total_evaluations
average_precision /= total_evaluations
average_recall /= total_evaluations
average_f1 /= total_evaluations

# Calculate average per-label metrics
label_metrics_final = []
for label, metrics in label_metrics.items():
    if metrics['count'] > 0:
        label_metrics_final.append({
            'class': label,
            'precision': metrics['precision'] / metrics['count'],
            'recall': metrics['recall'] / metrics['count'],
            'f1': metrics['f1'] / metrics['count']
        })

# Output the best results as JSON
results = {
    'best_k': best_k,
    'best_distance': best_distance,
    'best_p': best_p,
    'max_accuracy': max_accuracy,
    'best_precision': best_precision,
    'best_recall': best_recall,
    'best_f1': best_f1,
    'average_accuracy': average_accuracy,
    'average_precision': average_precision,
    'average_recall': average_recall,
    'average_f1': average_f1,
    'class_metrics': label_metrics_final
}

# Save results to a file
with open(results_path, 'w') as f:
    json.dump(results, f)

print(json.dumps(results))
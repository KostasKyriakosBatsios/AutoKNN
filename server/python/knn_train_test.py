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
    print("Usage: python knn_train_test.py <file> <features> <target> <k_values> <distances> <p_value> <stratified_sampling> <results_file_path>")
    sys.exit(1)

# The script arguments passed from PHP
file = sys.argv[1]
features = sys.argv[2].split(",")  # Comma-separated string -> list
target = sys.argv[3]
k_values = [int(k) for k in sys.argv[4].split(",")]  # Convert to list of integers
distance_values = sys.argv[5].split(",")  # Comma-separated string -> list

p_value = sys.argv[6] if sys.argv[6] and sys.argv[6] != 'null' else None
if p_value:
    p_value = [int(p) for p in sys.argv[6].split(",")]  # Convert to list of integers

stratified_sampling = sys.argv[7].lower() == 'true'  # Convert to boolean
results_path = sys.argv[8]

# Load dataset
dataset = pd.read_csv(file)

# Split the features and target variables
X = dataset[features].values
y = dataset[target].values

# Perform train test split (70% train, 30% test)
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42)

num_classes = len(set(y_train))

# Check if stratified sampling is needed
if stratified_sampling:
    # Perform stratified sampling on the training set
    X_sampled, _, y_sampled, _ = train_test_split(X_train, y_train, train_size=1000, random_state=42, stratify=y_train)
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

# Variables to calculate the weighted averages across all classes
average_accuracy = 0
total_evaluations = 0
total_precision_sum = 0
total_recall_sum = 0
total_f1_sum = 0
total_classes_count = 0

# Store per-label metrics
label_metrics = {label: {'precision': 0, 'recall': 0, 'f1': 0, 'count': 0} for label in set(y_test)}

# Evaluate different combinations
for k in k_values:
    for distance in distance_values:
        if distance == 'minkowski':
            for p in p_value:
                accuracy, precision, recall, f1, class_report = evaluate_knn(X_sampled, y_sampled, X_test, y_test, k, distance, p)
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
        
        # Accumulate overall metrics
        average_accuracy += accuracy
        total_evaluations += 1

        # Accumulate per-label metrics
        for label, metrics in class_report.items():
            if label not in ['accuracy', 'macro avg', 'weighted avg']:
                total_precision_sum += metrics['precision']
                total_recall_sum += metrics['recall']
                total_f1_sum += metrics['f1-score']
                total_classes_count += 1

                # Update per-label metrics for final report
                label_metrics[label]['precision'] += metrics['precision']
                label_metrics[label]['recall'] += metrics['recall']
                label_metrics[label]['f1'] += metrics['f1-score']
                label_metrics[label]['count'] += 1

# Finalize averages
average_accuracy /= total_evaluations

# Calculate the averages based on all classes
average_precision = total_precision_sum / total_classes_count if total_classes_count > 0 else 0
average_recall = total_recall_sum / total_classes_count if total_classes_count > 0 else 0
average_f1 = total_f1_sum / total_classes_count if total_classes_count > 0 else 0

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

# Output the best results and the parameters the user selected as JSON
results = {
    'dataset': file,
    'features': features,
    'class': target,
    'k_values': k_values,
    'distance_values': distance_values,
    'p_value': p_value,
    'stratified_sampling': stratified_sampling,
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
import time
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score

# Step 2: perform train - test split, so we'll find best k and metric distance
def knn(k_values, p_values, distances):
    dataset = pd.read_csv('dataset/normalized_iris.csv')
    X = dataset.drop(columns=['class']) # Features (attributes)
    y = dataset['class'] # Target variables (class labels)

    max_accuracy = 0
    best_k = 0
    best_p = 0
    best_distance = ''
    best_precision = 0
    best_recall = 0
    best_f1 = 0

    # Split dataset into train and test sets
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42)
    
    for distance in distances:
        for k in k_values:
            if distance == 'minkowski':
                for p in p_values:
                    # kNN classifier
                    clf = KNeighborsClassifier(n_neighbors=k, p=p, metric=distance)

                    # Fit classifier on train data
                    clf.fit(X_train, y_train)

                    # Make predictions on test data
                    y_pred = clf.predict(X_test)

                    accuracy = accuracy_score(y_test, y_pred)
                    precision = precision_score(y_test, y_pred, average='weighted')
                    recall = recall_score(y_test, y_pred, average='weighted')
                    f1 = f1_score(y_test, y_pred, average='weighted')

                    print(f"k={k}, p={p}, Distance={distance}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

                    if accuracy > max_accuracy:
                        max_accuracy = accuracy
                        best_k = k
                        best_p = p
                        best_distance = distance
                        best_precision = precision
                        best_recall = recall
                        best_f1 = f1
            else:
                # kNN classifier
                clf = KNeighborsClassifier(n_neighbors=k, metric=distance)

                # Fit classifier on train data
                clf.fit(X_train, y_train)

                # Make predictions on test data
                y_pred = clf.predict(X_test)

                accuracy = accuracy_score(y_test, y_pred)
                precision = precision_score(y_test, y_pred, average='weighted')
                recall = recall_score(y_test, y_pred, average='weighted')
                f1 = f1_score(y_test, y_pred, average='weighted')

                print(f"k={k}, Distance={distance}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

                if accuracy > max_accuracy:
                    max_accuracy = accuracy
                    best_k = k
                    best_p = None
                    best_distance = distance
                    best_precision = precision
                    best_recall = recall
                    best_f1 = f1
    
    print(f"Best execution: k={best_k}, p={best_p}, Distance={best_distance}, Accuracy={max_accuracy}, Precision={best_precision}, Recall={best_recall}, F1-score={best_f1}")
    return best_k, best_p, best_distance, max_accuracy, best_precision, best_recall, best_f1

if __name__ == "__main__":
    k_values = range(1, 51) # k=50 
    p_values = [3, 4] # p=3 and p=4
    distances = ['euclidean', 'manhattan', 'chebyshev', 'minkowski']

    start_time = time.time()
    
    best_k, best_p, best_distance, max_accuracy, best_precision, best_recall, best_f1 = knn(k_values, p_values, distances)

    end_time = time.time()

    execution_time = end_time - start_time
    print("Train-Test Split Execution Time:", execution_time, "seconds")
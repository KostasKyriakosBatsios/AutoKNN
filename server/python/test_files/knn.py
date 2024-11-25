import pandas as pd
import psutil
from sklearn.model_selection import train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score

# k Nearest Neighbor algorithm (kNN)
def knn(k_value, p_value, distance_value):
    # Load dataset
    dataset = pd.read_csv('server/python/public/normalized/iris_normalized.csv')

    # Split the features and target variables
    X = dataset.iloc[:, :-1].values # Features (attributes), basically all rows and all columns, except the last column
    y = dataset.iloc[:, -1].values # Target variables (class labels), basically all rows and the last column

    # Initialize variables for storing best metrics and parameters
    max_accuracy = 0
    best_k = 0
    best_p = None
    best_distance = ''
    best_precision = 0
    best_recall = 0
    best_f1 = 0

    # Step 2: Perform train test split (70% train, 30% set)
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42)

    print(f"X_train: {len(X_train)}, X_test: {len(X_test)}, y_train: {y_train}, y_test: {y_test}")

    # Check if at least one of the k and distance is "auto" by the user
    if isinstance(k_value, range) or isinstance(distance_value, list):
        # Perform stratified sampling on the 70% of the training set to get a number of samples randomized (max samples: near 70% training set)
        X_sampled, _, y_sampled, _ = train_test_split(X_train, y_train, train_size=8, random_state=42, stratify=y_train)
        
        # Check if k is "auto"
        if isinstance(k_value, range):
            for k in k_value:
                # Check if distance is "auto"
                if isinstance(distance_value, list):
                    for distance in distance_value:
                        # Check if distance is "minkowski"
                        if distance == "minkowski":
                            for p in p_value:
                                # kNN classifier
                                clf = KNeighborsClassifier(n_neighbors=k, p=p, metric=distance)

                                # Fit classifier on train data
                                clf.fit(X_sampled, y_sampled)

                                # Make predictions on test data
                                y_pred = clf.predict(X_test)

                                accuracy = accuracy_score(y_test, y_pred)
                                precision = precision_score(y_test, y_pred, average='weighted')
                                recall = recall_score(y_test, y_pred, average='weighted')
                                f1 = f1_score(y_test, y_pred, average='weighted')

                                print(f"k={k}, Distance={distance}, p={p}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

                                if accuracy > max_accuracy:
                                    max_accuracy = accuracy
                                    best_k = k
                                    best_p = p
                                    best_distance = distance
                                    best_precision = precision
                                    best_recall = recall
                                    best_f1 = f1

                        # distance is not "minkowski"
                        else:
                            # kNN classifier
                            clf = KNeighborsClassifier(n_neighbors=k, metric=distance)

                            # Fit classifier on train data
                            clf.fit(X_sampled, y_sampled)

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
                                best_distance = distance
                                best_precision = precision
                                best_recall = recall
                                best_f1 = f1
                
                # distance is not "auto"
                else:
                    # Check if distance is "minkowski"
                    if distance_value == "minkowski":
                        for p in p_value:
                            # kNN classifier
                            clf = KNeighborsClassifier(n_neighbors=k, p=p, metric=distance_value)

                            # Fit classifier on train data
                            clf.fit(X_sampled, y_sampled)

                            # Make predictions on test data
                            y_pred = clf.predict(X_test)

                            accuracy = accuracy_score(y_test, y_pred)
                            precision = precision_score(y_test, y_pred, average='weighted')
                            recall = recall_score(y_test, y_pred, average='weighted')
                            f1 = f1_score(y_test, y_pred, average='weighted')

                            print(f"k={k}, Distance={distance_value}, p={p}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

                            if accuracy > max_accuracy:
                                max_accuracy = accuracy
                                best_k = k
                                best_p = p
                                best_distance = distance_value
                                best_precision = precision
                                best_recall = recall
                                best_f1 = f1

                    # distance is not "minkowski"
                    else:
                        # kNN classifier
                        clf = KNeighborsClassifier(n_neighbors=k, metric=distance_value)
                        
                        # Fit classifier on train data
                        clf.fit(X_sampled, y_sampled)
                        
                        # Make predictions on test data
                        y_pred = clf.predict(X_test)
                        accuracy = accuracy_score(y_test, y_pred)
                        precision = precision_score(y_test, y_pred, average='weighted')
                        recall = recall_score(y_test, y_pred, average='weighted')
                        f1 = f1_score(y_test, y_pred, average='weighted')
                       
                        print(f"k={k}, Distance={distance_value}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")
                        
                        if accuracy > max_accuracy:
                            max_accuracy = accuracy
                            best_k = k
                            best_distance = distance_value
                            best_precision = precision
                            best_recall = recall
                            best_f1 = f1
        
        # k is not "auto"
        else:
            for distance in distance_value:
                # Check if distance is "minkowski"
                if distance == "minkowski":
                    for p in p_value:
                        # kNN classifier
                        clf = KNeighborsClassifier(n_neighbors=k_value, p=p, metric=distance)

                        # Fit classifier on train data
                        clf.fit(X_sampled, y_sampled)

                        # Make predictions on test data
                        y_pred = clf.predict(X_test)

                        accuracy = accuracy_score(y_test, y_pred)
                        precision = precision_score(y_test, y_pred, average='weighted')
                        recall = recall_score(y_test, y_pred, average='weighted')
                        f1 = f1_score(y_test, y_pred, average='weighted')

                        print(f"k={k_value}, Distance={distance}, p={p}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

                        if accuracy > max_accuracy:
                            max_accuracy = accuracy
                            best_k = k_value
                            best_p = p
                            best_distance = distance
                            best_precision = precision
                            best_recall = recall
                            best_f1 = f1

                # distance is not "minkowski"
                else:
                    # kNN classifier
                    clf = KNeighborsClassifier(n_neighbors=k_value, metric=distance)

                    # Fit classifier on train data
                    clf.fit(X_sampled, y_sampled)

                    # Make predictions on test data
                    y_pred = clf.predict(X_test)

                    accuracy = accuracy_score(y_test, y_pred)
                    precision = precision_score(y_test, y_pred, average='weighted')
                    recall = recall_score(y_test, y_pred, average='weighted')
                    f1 = f1_score(y_test, y_pred, average='weighted')

                    print(f"k={k_value}, Distance={distance}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

                    if accuracy > max_accuracy:
                        max_accuracy = accuracy
                        best_k = k_value
                        best_distance = distance
                        best_precision = precision
                        best_recall = recall
                        best_f1 = f1

    # None of them is "auto" by the user
    else:
        # Check if distance is "minkowski"
        if distance_value == 'minkowski':
            for p in p_value:
                # kNN classifier
                clf = KNeighborsClassifier(n_neighbors=k_value, p=p, metric=distance_value)

                # Fit classifier on train data
                clf.fit(X_train, y_train)

                # Make predictions on test data
                y_pred = clf.predict(X_test)

                accuracy = accuracy_score(y_test, y_pred)
                precision = precision_score(y_test, y_pred, average='weighted')
                recall = recall_score(y_test, y_pred, average='weighted')
                f1 = f1_score(y_test, y_pred, average='weighted')

                print(f"k={k_value}, Distance={distance_value}, p={p}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

                if accuracy > max_accuracy:
                    max_accuracy = accuracy
                    best_k = k_value
                    best_p = p
                    best_distance = distance_value
                    best_precision = precision
                    best_recall = recall
                    best_f1 = f1
        
        # distance is not "minkowski"
        else:
            # kNN classifier
            clf = KNeighborsClassifier(n_neighbors=k_value, metric=distance_value)

            # Fit classifier on train data
            clf.fit(X_train, y_train)

            # Make predictions on test data
            y_pred = clf.predict(X_test)

            accuracy = accuracy_score(y_test, y_pred)
            precision = precision_score(y_test, y_pred, average='weighted')
            recall = recall_score(y_test, y_pred, average='weighted')
            f1 = f1_score(y_test, y_pred, average='weighted')

            print(f"k={k_value}, Distance={distance_value}, Accuracy={accuracy}, Precision={precision}, Recall={recall}, F1-score={f1}")

            if accuracy > max_accuracy:
                max_accuracy = accuracy
                best_k = k_value
                best_distance = distance_value
                best_precision = precision
                best_recall = recall
                best_f1 = f1

    print(f"Best execution: k={best_k}, Distance={best_distance}, p={best_p}, Accuracy={max_accuracy}, Precision={best_precision}, Recall={best_recall}, F1-score={best_f1}")
    return best_k, best_distance, best_p, max_accuracy, best_precision, best_recall, best_f1


if __name__ == "__main__":
    # Ask the user for the value of k 
    while True:
        k = input("Enter a number or 'auto' for the value of k: ")

        # Check if k is a digit
        if k.isdigit():
            # While k is not between 1 and 50
            while int(k) < 1 or int(k) > 50:
                k = input("Enter a number or 'auto' for the value of k: ")
            k = int(k)
            break

        # Check if k is "auto"
        if (k.lower() == "auto"):
            k = range(1, 51, 2) # k = 50, with step=2
            break

    # Ask the user for the metric distance
    while True:
        distance = input("Enter either 'euclidean', 'manhattan', 'chebyshev', 'minkowski', or 'auto' for the value of the metric distance: ")

        # Check if distance is either "euclidean", "manhattan", "chebyshev", or "minkowski"
        if distance.lower() in ['euclidean', 'manhattan', 'chebyshev', 'minkowski']:
            distance = distance.lower()
            break

        # Check if distance is "auto"
        if distance.lower() == "auto":
            distance = ['euclidean', 'manhattan', 'chebyshev', 'minkowski']
            break

    # Initialization of p as None
    p = None

    # Check if the distance is the "minkowski" metric or the list of all the distances (it means that the user chose "auto")
    if distance == "minkowski" or isinstance(distance, list):
        # Change the value of p, if it gets inside this if
        p = range(1, 5)
    
    # Execution time (specifically CPU time)
    start_time = psutil.Process().cpu_times().user

    best_k, best_distance, best_p, max_accuracy, best_precision, best_recall, best_f1 = knn(k, p, distance)

    end_time = psutil.Process().cpu_times().user

    execution_time = end_time - start_time

    print("Train-Test Split Execution Time:", execution_time, "seconds")
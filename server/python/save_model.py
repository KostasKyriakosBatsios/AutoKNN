import sys
sys.path.insert(0, '/var/www/html/webkmeans/kclusterhub/autoknn/.venv/lib/python3.11/site-packages')
import joblib
import pandas as pd
from sklearn.neighbors import KNeighborsClassifier

def save_knn_model(file_path, features, target_class, k_value, distance_value, p_value, saved_model_file):
    # Load the dataset
    data = pd.read_csv(file_path)
    X = data[features]
    y = data[target_class]

    # Instantiate and configure the kNN model
    model = KNeighborsClassifier(n_neighbors=k_value, metric=distance_value, p=p_value)
    model.fit(X, y)

    # Save the model to a file
    try:
        joblib.dump(model, saved_model_file)
        print(f"Model saved successfully at {saved_model_file}")
    except Exception as e:
        print(f"Error saving the model file: {e}")
        sys.exit(1)


if len(sys.argv) != 8:
    print("Usage: python save_model.py <file_path>, <features>, <target_class>, <k_value>, <distance_value>, <p_value>, <saved_model_file>")
    print(f"Received arguments: {sys.argv}")
    sys.exit(1)

file_path = sys.argv[1]
features = sys.argv[2].split(",")
target_class = sys.argv[3]
k_value = int(sys.argv[4])
distance_value = sys.argv[5]

p_value = sys.argv[6] if sys.argv[6] and sys.argv[6] != 'null' else None
if p_value:
    p_value = int(p_value)  # Convert to integer if it's not None
    
saved_model_file = sys.argv[7]

# Call the function to save the model
save_knn_model(file_path, features, target_class, k_value, distance_value, p_value, saved_model_file)
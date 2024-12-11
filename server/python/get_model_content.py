import sys
sys.path.insert(0, '/var/www/html/webkmeans/kclusterhub/autoknn/.venv/lib/python3.11/site-packages')
import joblib
import json

if len(sys.argv) != 2:
    print(json.dumps({"error": "Invalid usage: Correct format is python get_model_content.py <model_path>"}))
    sys.exit(1)

model_file = sys.argv[1]
model = joblib.load(model_file)

# Extract features
columns = model.feature_names_in_.tolist()

# Print both features and class as JSON
print(json.dumps({"features": columns}))
import sys
import os
import pandas as pd
from sklearn.preprocessing import MinMaxScaler

# Check if the user provided the correct number of arguments
if len(sys.argv) != 3:
    print("Usage: python normalization.py <file_path> <output_folder>")
    sys.exit(1)

# Get the file path and output folder from the command line arguments
file_path = sys.argv[1]
output_folder = sys.argv[2]
print(f"Processing file: {file_path}")

# Load the dataset
try:
    df = pd.read_csv(file_path)
except Exception as e:
    print(f"Error reading CSV: {e}")
    sys.exit(1)

# Perform Min-Max scaling (normalize numeric columns)
numeric_columns = df.select_dtypes(include=['float64', 'int64']).columns
scaler = MinMaxScaler()
df[numeric_columns] = scaler.fit_transform(df[numeric_columns])

# Ensure the output directory exists
os.makedirs(output_folder, exist_ok=True)

# Save the normalized dataset in the specified output folder
normalized_file_name = os.path.basename(file_path).replace('.csv', '_normalized.csv')
normalized_file_path = os.path.join(output_folder, normalized_file_name)
df.to_csv(normalized_file_path, index=False)

print(f"Normalization successful. Output file: {normalized_file_path}")
# Read the magic.dat file (create a folder name datasets and put the file in it)
with open("datasets/magic.dat", "r") as file:
    lines = file.readlines()

# Extract attribute names
attributes = [line.split()[1] for line in lines if line.startswith("@attribute")]

# Extract data rows starting after the @data line
data_start = lines.index('@data\n') + 1
data_rows = [line.strip() for line in lines[data_start:]]

# Write the data to magic.csv
with open("datasets/magic.csv", "w") as file:
    # Write attribute names as header
    file.write(",".join(attributes) + "\n")
    # Write data rows
    file.write("\n".join(data_rows))

print("Conversion completed successfully.")

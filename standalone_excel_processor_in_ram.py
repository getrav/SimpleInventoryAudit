
import pandas as pd
import sys
import tempfile
import shutil

def process_excel_files(input_files):
    """ 
    Process a list of Excel files.
    
    Args:
    - input_files (list): List of paths to Excel files to process.
    
    Returns:
    - list: List of paths to the refactored Excel files.
    """
    output_files = []

    for input_file in input_files:
        # Load the entire Excel file into a temporary file in RAM
        with tempfile.NamedTemporaryFile(delete=False) as temp_file:
            shutil.copy(input_file, temp_file.name)

            # Read data from the in-memory Excel file into a DataFrame
            data = pd.read_excel(temp_file.name)

        # Sort the data based on the criteria we discussed earlier
        sorted_data = data.sort_values(by=["Location Code", "Item No.", "Zone Code", "Bin Code", "Lot No."])

        # Group by the specified columns and aggregate the Quantity
        grouped_data = sorted_data.groupby(["Location Code", "Item No.", "Lot No.", "Bin Code"]).agg({"Quantity": "sum"}).reset_index()

        # Filter out entries with a total quantity of 0
        grouped_data = grouped_data[grouped_data["Quantity"] != 0]

        # Save the refactored data to an Excel file on disk
        output_file = input_file.replace(".xlsx", "_refactored.xlsx")
        grouped_data.to_excel(output_file, index=False)

        output_files.append(output_file)

    return output_files

if __name__ == "__main__":
    # Check if at least one input file is provided
    if len(sys.argv) < 2:
        print("Please provide at least one Excel file as an argument.")
        sys.exit(1)

    # Get the input files from the command-line arguments
    input_files = sys.argv[1:]

    # Call the function
    output_files = process_excel_files(input_files)

    # Print the paths to the refactored Excel files
    for output_file in output_files:
        print(f"Refactored data saved to: {output_file}")


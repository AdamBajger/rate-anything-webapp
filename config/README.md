# Rating Configuration Files

This directory contains YAML configuration files that define different rating contexts for the rate-anything-webapp.

## Configuration File Structure

Each configuration file sets up a specific rating context (e.g., coffee brands, products, services) and contains the following elements:

### Required Fields

- **ratings_storage**: Name of the YAML file where ratings will be stored
- **name_extraction_regex**: Regular expression pattern to extract human-readable names from QR code identifiers
- **display**: Display texts and titles for the web application

### Display Configuration

The `display` section includes:
- `page_title`: Browser tab title
- `main_heading`: Main page heading
- `rating_subject`: What is being rated (e.g., "coffee brand")
- `instructions`: User instructions text
- `success_message`: Message shown after rating submission
- `item_label`: Label for the rated item

### Optional Fields

- **rating_scale**: Define min/max values and labels for ratings
- **rating_categories**: Multiple rating categories with descriptions

## Example: Coffee Brands

See `coffee_brands.yaml` for a complete example of a coffee brand rating configuration.

### Usage

The configuration enables:
1. **QR Code Scanning**: Users scan QR codes on coffee packages
2. **Name Extraction**: The regex pattern extracts brand names from identifiers
   - Example: `coffee_brand_starbucks` → `starbucks`
   - Example: `cb_lavazza` → `lavazza`
3. **Rating Interface**: Displays appropriate titles and instructions
4. **Data Storage**: Saves ratings to the specified YAML file

## Creating New Configurations

To create a new rating context:

1. Copy `coffee_brands.yaml` as a template
2. Modify the `ratings_storage` filename
3. Update the `name_extraction_regex` to match your identifier format
4. Customize all `display` texts for your context
5. Adjust `rating_scale` and `rating_categories` as needed

## Identifier Format

QR code identifiers should follow a consistent pattern that matches your regex:
- Coffee brands: `coffee_brand_<brand_name>` or `cb_<brand_name>`
- Example identifiers: `coffee_brand_starbucks`, `cb_lavazza`, `cb_illy`

The regex captures the brand name portion, which is then displayed to users in a human-readable format.

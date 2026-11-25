# Product Module
app/
└── Modules/
    └── Product/
        ├── Controllers/
        │   └── ProductController.php
        │
        ├── Database/
        │   ├── Migrations/
        │   │   ├── xxxx_create_products_table.php
        │   │   ├── xxxx_create_product_images_table.php
        │   │   ├── xxxx_create_product_variants_table.php
        │   │   ├── xxxx_create_product_attributes_table.php
        │   │   └── xxxx_create_product_attribute_values_table.php
        │   │
        │   └── Seeders/
        │
        ├── Models/
        │   ├── Product.php
        │   ├── ProductImage.php
        │   ├── ProductVariant.php
        │   ├── ProductAttribute.php
        │   └── ProductAttributeValue.php
        │
        ├── Providers/
        │   └── ProductServiceProvider.php
        │
        ├── Repositories/
        │   ├── Contracts/
        │   │   └── ProductRepositoryInterface.php
        │   └── ProductRepository.php
        │
        ├── Requests/
        │   ├── StoreProductRequest.php
        │   └── UpdateProductRequest.php
        │
        ├── Resources/
        │   ├── ProductResource.php
        │   ├── ProductListResource.php
        │   └── ProductVariantResource.php
        │
        ├── Routes/
        │   └── api.php
        │
        └── Services/
            └── ProductService.php





Product
│
├── Category
│   └── Electronics
│       └── Mobiles
│           └── Smartphones
│
├── Basic information
│   ├── name
│   ├── slug
│   ├── SKU
│   ├── description
│   └── status
│
├── Pricing
│   ├── price
│   ├── compare_at_price
│   ├── cost_price
│   └── tax
│
├── Inventory
│   ├── stock
│   ├── low_stock_threshold
│   └── stock_status
│
├── Images
│   ├── primary image
│   ├── gallery images
│   └── sort order
│
├── Attributes
│   ├── Brand
│   ├── Color
│   ├── RAM
│   └── Storage
│
└── Variants
    ├── iPhone 17 / Black / 256GB
    ├── iPhone 17 / Black / 512GB
    ├── iPhone 17 / White / 256GB
    └── ...



Product/
├── Controllers/
│   └── ProductController.php
│
├── Database/
│   └── Migrations/
│       ├── create_products_table
│       ├── create_product_images_table
│       ├── create_attributes_table
│       ├── create_attribute_values_table
│       ├── create_product_attribute_values_table
│       ├── create_product_options_table
│       ├── create_product_option_values_table
│       ├── create_product_variants_table
│       └── create_product_variant_option_values_table
│
├── Models/
│   ├── Product.php
│   ├── ProductImage.php
│   ├── Attribute.php
│   ├── AttributeValue.php
│   ├── ProductOption.php
│   ├── ProductOptionValue.php
│   └── ProductVariant.php
│
├── Repositories/
├── Requests/
├── Resources/
├── Services/
└── routes/



Step 1 — products

Step 2 — product_images


Step 3 — attributes
    An attribute is something that describes a product.
    Examples:
    Brand
    Material
    RAM
    Storage
    Screen Size
    Battery


Step 4 — attribute_values
    An attribute can have many values.
    For example:
    Attribute: Brand
    Values:
    Samsung
    Apple
    Xiaomi
    Sony


Step 5 — Product Attribute Values
    This connects:
    Product ←→ Attribute Value
    Example:
    iPhone 17
        Brand → Apple
        Storage → 256GB
        RAM → 8GB


Step 6 — Product Options
    This is different from attributes.
    Options are things customers choose to create a variant.
    Examples:
    Color
    Size
    Storage
    RAM        



Step 7 — Product Option Values
    Example:
    Color
    ├── Red
    ├── Blue
    └── Black


Step 8 — Product Variants
    This is the most important table for variable products.


Step 9 — Variant ↔ Option Values
    This table tells us exactly what a variant represents.
    For example:
    Variant #1
        Color = Red
        Size = M



Samsung Galaxy
│
├── Category
│   └── Electronics
│       └── Mobiles
│           └── Smartphones
│
├── Attributes
│   ├── Brand → Samsung
│   ├── Display → AMOLED
│   └── Battery → 5000mAh
│
├── Options
│   ├── Color
│   │   ├── Black
│   │   └── Blue
│   │
│   └── Storage
│       ├── 128GB
│       └── 256GB
│
└── Variants
    ├── Black / 128GB
    ├── Black / 256GB
    ├── Blue / 128GB
    └── Blue / 256GB



Product
 ├── belongsTo Category
 ├── hasMany ProductImage
 ├── belongsToMany AttributeValue
 ├── hasMany ProductAttributeValue
 ├── hasMany ProductOption
 └── hasMany ProductVariant

Attribute
 └── hasMany AttributeValue

AttributeValue
 ├── belongsTo Attribute
 └── belongsToMany Product

ProductOption
 └── hasMany ProductOptionValue

ProductOptionValue
 ├── belongsTo ProductOption
 └── belongsToMany ProductVariant

ProductVariant
 ├── belongsTo Product
 └── belongsToMany ProductOptionValue


 The ProductService will handle:
    Creating products
    Updating products
    Deleting products
    Product images
    Primary image handling
    Attributes
    Options
    Variants
    Variant option combinations
    Transactions
    Validation-related business rules

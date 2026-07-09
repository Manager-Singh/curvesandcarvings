# Curves & Carvings — Product Variations Admin Guide

This guide explains how to manage **configurable product variations** on staging/live after the `Curvesandcarvings_Variations` module is installed.

The module creates **7 variation attributes** and **7 attribute sets** (one per furniture family). Each product type uses its **own attribute label and values**.

---

## Category → Attribute set mapping

| Magento category (examples) | Attribute set | Variation attribute | Customer sees |
|----------------------------|---------------|---------------------|---------------|
| Bedroom Sets, Bed Room set | **India - Bedroom Sets** | `bedroom_set_config` | Choose Option: Bed Only, Bed + Dressing Table, Bed + Side Tables, Full Set |
| BEDS (single beds) | **India - Beds** | `bed_size` | Select Bed Size: Single, Queen, King, Custom Size |
| DINING TABLE (tables only) | **India - Dining Tables** | `seating_capacity` | Select Seating Capacity: 4 / 6 / 8 / 9+ Seater |
| Dining Room set | **India - Dining Sets** | `dining_set_config` | Choose Option: Table Only, Table + 4 Chairs, Table + 6 Chairs, Full Dining Set |
| SOFAS, Diwans | **India - Sofas** | `sofa_configuration` | Choose Option: 1 / 2 / 3 Seater, Full Sofa Set |
| Wardrobes | **India - Wardrobes** | `wardrobe_doors` | Select Doors: 2 / 4 / 6 Door |
| Chest of Drawers | **India - Chests** | `drawer_config` | Choose Configuration: Standard, Large, With Mirror |
| Chairs, Console Tables, Accent pieces, etc. | **india** (default) | — | No variation attribute — keep as **Simple** |

---

## Reference example products (staging)

After running `php scripts/create-variation-examples.php`, these products are configurable references:

| Family | Parent SKU | Child SKU suffixes |
|--------|------------|-------------------|
| Bedroom Set | `C&C BED0222-IND` | `-BED`, `-DRESSING`, `-SIDE`, `-FULL` |
| Bed | `C&C BED0001-IND` | `-SINGLE`, `-QUEEN`, `-KING` |
| Dining Table | `C&C DTC0005-IND` | `-4S`, `-6S`, `-8S` |
| Sofa | `C&C SOF0002-IND` | `-1S`, `-2S`, `-3S` |
| Wardrobe | `C&C WAR0003-IND` | `-2D`, `-4D` |
| Chest | `C&C CAB0001-IND` | `-STD`, `-LARGE`, `-MIRROR` |

Open any of these in admin to see the finished structure.

---

## How to convert a simple product to configurable (admin)

### Step 1 — Pick the right attribute set

1. **Catalog → Products** → open the product.
2. **Attribute Set** dropdown → select the set for that category (see table above).
3. **Save** (product may reload).

### Step 2 — Create child simple products (one per variation)

For each variation (e.g. Bed Only, Full Set):

1. **Catalog → Products → Add Product → Simple Product**
2. **Attribute Set**: same as parent (e.g. India - Bedroom Sets).
3. **SKU**: `PARENT-SKU-SUFFIX` (e.g. `C&C BED0222-IND-FULL`).
4. **Name**: descriptive (e.g. same name + ` - Full Set`).
5. **Price / Special Price**: set the real price for this variation.
6. **Quantity / Stock**: set stock for this variation.
7. **Images**: upload images for this variation only.
8. **Variations** group: set the attribute value (e.g. Full Set).
9. **Visibility**: **Not Visible Individually**.
10. **Categories**: leave empty (do not assign categories).
11. **Customizable Options**: do **not** add Payment Options on children — only on the parent.
12. **Save**.

Repeat for every variation value you sell.

### Step 3 — Convert parent to Configurable

1. Open the **parent** product (original simple SKU).
2. Change **Product Type** to **Configurable Product** (or use Configurations section if already configurable).
3. Scroll to **Configurations** → **Create Configurations**.
4. Select the variation attribute (e.g. Choose Option / `bedroom_set_config`).
5. Select all values that apply.
6. **Next** → choose **Select existing products** and pick the child SKUs you created.
7. **Generate** → **Save** parent.

### Step 4 — Keep payment options on parent only

- **Payment Options** (50% Advance, EMI) and **Buy back** stay on the **configurable parent**.
- Child products must **not** have these custom options.

### Step 5 — Reindex

After bulk changes:

```bash
php bin/magento indexer:reindex catalog_product_price catalogsearch_fulltext
php bin/magento cache:flush
```

Or **System → Index Management → Update on Save** (if indexers are invalid).

---

## SKU naming rules

```
Parent (configurable, visible):     C&C BED0222-IND
Children (simple, hidden):          C&C BED0222-IND-BED
                                    C&C BED0222-IND-DRESSING
                                    C&C BED0222-IND-FULL
```

Use short, clear suffixes. Keep the parent SKU unchanged so existing URLs and links keep working.

---

## Rules checklist

| Rule | Parent | Children |
|------|--------|----------|
| Product type | Configurable | Simple |
| Visibility | Catalog, Search | Not Visible Individually |
| Categories | Yes | No |
| Payment custom options | Yes | No |
| Own price | No (from children) | Yes |
| Own stock | No | Yes |
| Own images | Default / fallback | Yes, per variation |

---

## Different labels per product type

Each attribute has its **own admin label** (shown on the product page):

- Bedroom sets → **Choose Option**
- Beds → **Select Bed Size**
- Dining tables → **Select Seating Capacity**
- Wardrobes → **Select Doors**

You do not need one shared label for all products. Pick the attribute set that matches the product category.

---

## Products that should stay Simple

Do **not** convert to configurable if:

- The product has only one fixed configuration (most chairs, console tables, single chests).
- You only need payment terms (50% advance / EMI) — custom options are enough.

---

## Deploying structure to live

1. Deploy `app/code/Curvesandcarvings/Variations/` to live.
2. Run:
   ```bash
   php bin/magento module:enable Curvesandcarvings_Variations
   php bin/magento setup:upgrade
   php bin/magento cache:flush
   ```
3. Attributes and attribute sets are created automatically by data patches.
4. Convert products on live using this guide (or run the example script on a test product first).

---

## Module location

```
app/code/Curvesandcarvings/Variations/
├── registration.php
├── etc/module.xml
└── Setup/Patch/Data/
    ├── CreateVariationAttributes.php
    └── CreateVariationAttributeSets.php
```

Example script (optional, not part of module):

```
scripts/create-variation-examples.php
```

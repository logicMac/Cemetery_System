# Multiple Records Per Compartment Feature

## Overview

This feature allows **multiple burial records** to be stored in a single compartment (yellow markers on the map). This is essential for:
- **Family plots** where multiple family members are buried together
- **Multi-level burials** (stacked graves at different depths)
- **Shared cemetery plots** with multiple occupants

## Database Schema

### New Table: `compartment_records`
Junction table enabling many-to-many relationships between compartments and burial records.

```sql
CREATE TABLE compartment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compartment_id INT NOT NULL,           -- Links to plot_compartments.id
    record_id INT NOT NULL,                -- Links to burial_records.id
    burial_level VARCHAR(20) DEFAULT 'ground',  -- ground, first, second, third
    position_notes TEXT,                   -- Additional position info
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (compartment_id) REFERENCES plot_compartments(id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES burial_records(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_compartment_record (compartment_id, record_id)
);
```

### Updated Table: `plot_compartments`
Added capacity tracking:

```sql
ALTER TABLE plot_compartments 
    ADD COLUMN capacity INT DEFAULT 1,          -- How many records compartment can hold
    ADD COLUMN occupied_count INT DEFAULT 0;    -- Current number of records
```

## Installation

1. **Run the migration:**
   ```bash
   # Navigate to database and import
   mysql -u root cemetery_mapping1 < add_compartment_records.sql
   ```

2. **Verify tables:**
   - Check that `compartment_records` table exists
   - Check that `plot_compartments` has `capacity` and `occupied_count` columns

## API Endpoints

### `/api/compartment_records.php`

All actions require admin authentication (`$_SESSION['admin_id']`).

#### Actions

##### 1. List Records in Compartment
```javascript
GET/POST: action=list&compartment_id=123

Response:
{
  "success": true,
  "message": "Records retrieved",
  "data": {
    "records": [
      {
        "id": 1,
        "compartment_id": 123,
        "record_id": 456,
        "burial_level": "ground",
        "position_notes": "North side",
        "decedent_name": "John Doe",
        "birth_date": "1950-01-15",
        "death_date": "2023-05-20",
        "family_name": "Doe Family",
        "compartment_number": "A-12",
        "capacity": 3,
        "occupied_count": 1
      }
    ]
  }
}
```

##### 2. Add Record to Compartment
```javascript
POST: {
  "action": "add",
  "compartment_id": 123,
  "record_id": 456,
  "burial_level": "ground",      // optional: ground, first, second, third
  "position_notes": "North side" // optional
}

Response:
{
  "success": true,
  "message": "Record added to compartment",
  "data": {
    "id": 1,
    "compartment_id": 123,
    "record_id": 456
  }
}

Error cases:
- Compartment is full (capacity reached)
- Record already in compartment
- Compartment or record not found
```

##### 3. Remove Record from Compartment
```javascript
POST: {
  "action": "remove",
  "id": 1  // Junction table ID
}

// OR

POST: {
  "action": "remove",
  "compartment_id": 123,
  "record_id": 456
}

Response:
{
  "success": true,
  "message": "Record removed from compartment"
}
```

##### 4. Update Compartment Capacity
```javascript
POST: {
  "action": "update_capacity",
  "compartment_id": 123,
  "capacity": 5
}

Response:
{
  "success": true,
  "message": "Compartment capacity updated",
  "data": {
    "capacity": 5
  }
}

Error cases:
- New capacity less than current occupied_count
- Capacity < 1
```

##### 5. Get Compartments for a Record
```javascript
GET/POST: action=get_by_record&record_id=456

Response:
{
  "success": true,
  "message": "Compartments retrieved",
  "data": {
    "compartments": [
      {
        "id": 1,
        "compartment_id": 123,
        "record_id": 456,
        "burial_level": "ground",
        "compartment_number": "A-12",
        "row_index": 0,
        "col_index": 1,
        "plot_id": 10,
        "plot_number": "Section A",
        "latitude": 6.1834,
        "longitude": 125.0846
      }
    ]
  }
}
```

## Admin UI

### Compartment Records Manager
**Path:** `/admin/compartment-manager.php`

**Features:**
1. **Plot & Compartment Selection**
   - Dropdown lists all plots with compartments
   - Shows compartment capacity and occupancy stats
   
2. **Record Management**
   - View all records in a compartment
   - Add new records (with search)
   - Remove records
   - Set burial level (ground, first, second, third)
   - Add position notes

3. **Capacity Management**
   - View current capacity vs occupancy
   - Update compartment capacity
   - Validation prevents capacity reduction below current occupancy

**Access:** Admin panel → Cemetery Map → Compartment Records

## Frontend Integration

### Updated `get_available_plots.php`

Now returns enriched compartment data:

```javascript
{
  "success": true,
  "plots": [
    {
      "id": 10,
      "plot_number": "Section A",
      "has_grid": 1,
      "total_compartments": 12,
      "occupied_compartments": 5,
      "compartments": [
        {
          "id": 123,
          "compartment_number": "A-12",
          "row_index": 0,
          "col_index": 1,
          "is_occupied": 1,
          "capacity": 3,
          "occupied_count": 2,  // 2 out of 3 records
          "record_count": 2
        }
      ]
    }
  ]
}
```

### Map Display Updates

To show capacity on map markers:

```javascript
// When rendering compartment markers
const compartment = plot.compartments[i];
const markerColor = compartment.occupied_count >= compartment.capacity 
    ? 'red'    // Full
    : compartment.occupied_count > 0 
    ? 'yellow' // Partially occupied
    : 'green'; // Empty

const marker = L.marker([lat, lng], {
    icon: getCompartmentIcon(markerColor)
}).bindPopup(`
    <b>${compartment.compartment_number}</b><br>
    Occupied: ${compartment.occupied_count} / ${compartment.capacity}
`);
```

## Usage Examples

### Scenario 1: Family Plot with Multiple Burials

1. Admin creates a plot with compartments (2x2 grid)
2. Sets compartment A-1 capacity to 4 (family of 4)
3. Over time, adds 4 family members to compartment A-1:
   - Grandfather (ground level)
   - Grandmother (first level)
   - Uncle (second level)
   - Aunt (third level)

### Scenario 2: Stacked Burials

1. Compartment B-5 has capacity 2
2. Add first record: burial_level = "ground"
3. Add second record: burial_level = "first"
4. Compartment is now full (2/2)

## Migration from Old System

The migration script automatically:
1. Creates the `compartment_records` table
2. Migrates existing `plot_compartments.record_id` values to junction table
3. Sets initial `capacity` to 1 for all existing compartments
4. Calculates `occupied_count` based on migrated data
5. Preserves old `record_id` column for backwards compatibility

**Note:** Old code using `plot_compartments.record_id` will continue to work, but new code should use the `compartment_records` junction table.

## Benefits

✅ **Flexibility:** Support for multi-level and family burials  
✅ **Accurate tracking:** Know exactly how many records per compartment  
✅ **Capacity management:** Prevent over-booking  
✅ **Better data:** Track burial level and position notes  
✅ **Backwards compatible:** Doesn't break existing single-record compartments  

## Future Enhancements

- Visual grid display showing record count per compartment
- Color-coding based on capacity utilization
- Bulk operations (move multiple records)
- Family grouping visualization
- Auto-suggest next available compartment with capacity

## Support

For issues or questions:
- Check database migrations are applied
- Verify API endpoint authentication
- Check browser console for JavaScript errors
- Ensure `plot_compartments` table has new columns

## Technical Notes

- All API endpoints use prepared statements (PDO)
- Foreign keys maintain referential integrity
- Cascade deletes prevent orphaned records
- Capacity validation prevents data inconsistency
- Transaction support for multi-step operations

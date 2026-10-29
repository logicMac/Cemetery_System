# Setup Guide: Multiple Records Per Compartment

## Quick Start (5 Steps)

### Step 1: Run the Database Migration

Open phpMyAdmin or MySQL command line:

```bash
# Using MySQL command line
cd C:\wamp64\www\client_cemeteryV2\cemetery_mapping
mysql -u root cemetery_mapping1 < add_compartment_records.sql

# OR using phpMyAdmin:
# 1. Select database 'cemetery_mapping1'
# 2. Go to 'Import' tab
# 3. Choose file 'add_compartment_records.sql'
# 4. Click 'Go'
```

**Verify:**
```sql
-- Check if new table exists
SHOW TABLES LIKE 'compartment_records';

-- Check if new columns exist
SHOW COLUMNS FROM plot_compartments LIKE 'capacity';
SHOW COLUMNS FROM plot_compartments LIKE 'occupied_count';
```

### Step 2: Access the Compartment Manager

1. Login to admin panel: `http://localhost/client_cemeteryV2/cemetery_mapping/admin/`
2. Navigate to: **Cemetery Map → Compartment Records**
3. You should see the Compartment Records Manager page

### Step 3: Select a Plot with Compartments

1. From the **"Select Plot with Compartments"** dropdown, choose a plot
   - Only plots with compartments will appear
   - Example: "Plot A-1 (12 compartments)"

2. Then select a **compartment** from the second dropdown
   - Shows current occupancy, e.g., "A-12 - 2/5 occupied"

3. View compartment info:
   - **Capacity:** Maximum records this compartment can hold
   - **Occupied:** Current number of records
   - **Available:** Remaining space

### Step 4: Add Records to Compartment

1. Click **"Add Record"** button

2. In the modal:
   - **Search** for a burial record by name or plot number
   - Select a record from search results
   - Choose **burial level**: ground, first, second, third
   - Optionally add **position notes** (e.g., "North side, near entrance")
   - Click **"Add to Compartment"**

3. The record will appear in the compartment's record list

### Step 5: Manage Capacity (Optional)

If you need to increase compartment capacity:

1. Click **"Change Capacity"** button
2. Enter new capacity number
3. Click **"Update Capacity"**

**Note:** You cannot reduce capacity below the current number of occupied records.

---

## Example Scenarios

### Scenario A: Family Plot Setup

**Goal:** Create a family plot for the "Dela Cruz" family with 4 burial spaces

1. **Create the plot** (if not already created):
   - Go to **Available Plots**
   - Add new plot with grid enabled (2x2 = 4 compartments)

2. **Set compartment capacity**:
   - Go to **Compartment Records**
   - Select the plot and compartment A-1
   - Click "Change Capacity" → Set to **4**

3. **Add family members over time**:
   
   **First burial (2020):**
   - Add Record: "Pedro Dela Cruz"
   - Level: ground
   - Notes: "Family patriarch"
   
   **Second burial (2022):**
   - Add Record: "Maria Dela Cruz"
   - Level: first
   - Notes: "Spouse of Pedro"
   
   **Future burials:**
   - Can add 2 more records (capacity 4, occupied 2)

### Scenario B: Stacked Graves (Vertical Burial)

**Goal:** 3-level burial chamber

1. **Set compartment capacity to 3**

2. **Add records at different levels**:
   - Record 1: Level = "ground"
   - Record 2: Level = "first" (1.5m depth)
   - Record 3: Level = "second" (3m depth)

3. **Result:** 
   - Single location (lat/lng) with 3 burial records
   - Each at different vertical level

### Scenario C: Migrating Existing Single-Record Compartments

**Existing setup:**
- Compartment has 1 record assigned via old `record_id` column

**Migration:**
- The migration script automatically moves this to `compartment_records` table
- Capacity defaults to 1 (can be increased later)
- No manual action needed!

**To add more records:**
1. Go to Compartment Records Manager
2. Select the compartment
3. Change capacity to 2 or more
4. Add additional records

---

## Map Visualization Suggestions

### Color-Coding Compartments by Capacity

Modify `visitor.js` or map rendering code:

```javascript
function getCompartmentColor(occupied, capacity) {
    if (occupied === 0) return 'green';           // Empty
    if (occupied < capacity) return 'yellow';      // Partially filled
    if (occupied >= capacity) return 'red';        // Full
}

// When rendering markers
plot.compartments.forEach(comp => {
    const color = getCompartmentColor(comp.occupied_count, comp.capacity);
    const marker = L.marker([lat, lng], {
        icon: L.divIcon({
            html: `<div style="background: ${color}; ...">...</div>`
        })
    });
});
```

### Popup Content with Record List

```javascript
marker.bindPopup(`
    <div class="compartment-popup">
        <h3>${comp.compartment_number}</h3>
        <p>Capacity: ${comp.occupied_count} / ${comp.capacity}</p>
        <button onclick="viewCompartmentRecords(${comp.id})">
            View Records (${comp.occupied_count})
        </button>
    </div>
`);
```

---

## API Integration Examples

### Frontend: Add Record to Compartment

```javascript
async function addRecordToCompartment(compartmentId, recordId, level) {
    const response = await fetch('/api/compartment_records.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'add',
            compartment_id: compartmentId,
            record_id: recordId,
            burial_level: level,
            position_notes: 'Added via visitor portal'
        })
    });
    
    const data = await response.json();
    
    if (data.success) {
        console.log('Record added:', data.data);
        alert('Record added to compartment!');
    } else {
        alert('Error: ' + data.message);
    }
}
```

### Frontend: List All Records in Compartment

```javascript
async function loadCompartmentRecords(compartmentId) {
    const response = await fetch(
        `/api/compartment_records.php?action=list&compartment_id=${compartmentId}`
    );
    const data = await response.json();
    
    if (data.success) {
        data.data.records.forEach(record => {
            console.log(`${record.decedent_name} - Level: ${record.burial_level}`);
        });
    }
}
```

### Backend: PHP Query All Compartments with Record Counts

```php
$stmt = $pdo->query("
    SELECT 
        pc.*,
        COUNT(cr.id) as actual_record_count
    FROM plot_compartments pc
    LEFT JOIN compartment_records cr ON pc.id = cr.compartment_id
    WHERE pc.plot_id = ?
    GROUP BY pc.id
    ORDER BY pc.row_index, pc.col_index
");
$stmt->execute([$plotId]);
$compartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

---

## Troubleshooting

### Issue: "Table 'compartment_records' doesn't exist"

**Solution:**
```bash
# Run the migration
mysql -u root cemetery_mapping1 < add_compartment_records.sql
```

### Issue: "Cannot add record - compartment is full"

**Solution:**
1. Go to Compartment Records Manager
2. Select the compartment
3. Click "Change Capacity"
4. Increase the capacity number

### Issue: "Column 'capacity' doesn't exist in plot_compartments"

**Solution:**
```sql
-- Manually add columns
ALTER TABLE plot_compartments 
    ADD COLUMN capacity INT DEFAULT 1,
    ADD COLUMN occupied_count INT DEFAULT 0;
```

### Issue: Records not showing in compartment

**Solution:**
1. Check that `compartment_records` table has data:
   ```sql
   SELECT * FROM compartment_records WHERE compartment_id = ?;
   ```
2. Verify foreign keys are correct
3. Check that `occupied_count` was updated

### Issue: Old record_id column conflicts

**Solution:**
The system supports both old and new methods. The migration preserves `record_id` for backwards compatibility. New code uses `compartment_records` table. Both work simultaneously.

---

## Performance Considerations

### Index Optimization

The migration creates indexes automatically, but verify:

```sql
SHOW INDEX FROM compartment_records;

-- Should have indexes on:
-- - compartment_id
-- - record_id
-- - unique (compartment_id, record_id)
```

### Query Optimization

When loading plots with many compartments:

```php
// Good: Batch load in single query
$stmt = $pdo->prepare("
    SELECT 
        pc.*,
        COUNT(cr.id) as record_count
    FROM plot_compartments pc
    LEFT JOIN compartment_records cr ON pc.id = cr.compartment_id
    WHERE pc.plot_id IN (?, ?, ?)
    GROUP BY pc.id
");

// Avoid: N+1 queries (loading each compartment separately)
```

---

## Security Notes

✅ All API endpoints require admin authentication  
✅ SQL injection protected via prepared statements  
✅ Foreign key constraints prevent orphaned records  
✅ Capacity validation prevents data corruption  
✅ Input sanitization on all user inputs  

---

## Summary

✨ **You now have:**
- Multi-record compartments for family plots
- Capacity tracking and management
- Burial level classification (ground, first, second, third)
- Position notes for precise location
- Full CRUD operations via API
- Admin UI for easy management

🚀 **Next steps:**
- Update map markers to show capacity status
- Add visual indicators for full/partial/empty compartments
- Create visitor-facing compartment info view
- Add bulk import for family plot setup

---

## Support

Need help? Check:
1. **Database:** Verify migrations ran successfully
2. **Logs:** Check browser console and PHP error logs
3. **Permissions:** Ensure admin session is active
4. **Documentation:** Review `COMPARTMENT_RECORDS_README.md`

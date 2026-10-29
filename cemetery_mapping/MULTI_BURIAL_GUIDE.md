# Multiple Burials at Same Location - Quick Guide

## Current Situation
You have 32 burial records and want to add more burials to the same coordinates (family plots, multi-level burials, etc.).

## Two Ways to Achieve This

### Option 1: Manual Method (Current System)
When adding a new burial record:
1. Go to **Admin → Add Record**
2. Fill in the decedent details
3. **Copy the coordinates** from an existing record at that location:
   - Latitude: `6.xxxxx`
   - Longitude: `125.xxxxx`
4. Paste the same coordinates for the new burial
5. Use the **Plot Number** field to indicate it's the same plot (e.g., "A1-Level2", "FamilyPlot-A")
6. Save

**Pros:** Works right now, no changes needed  
**Cons:** Manual copying of coordinates, prone to errors

---

### Option 2: Compartment System (Recommended)

This is what the Compartment Manager is designed for - managing multiple burials at the same location.

#### Step 1: Create Available Plots from Your Existing Burials

Run the tool: `/admin/create_plots_from_burials.php`

This will:
- Analyze your 32 burial records
- Find unique plot locations
- Create "Available Plot" entries with compartments
- Each plot gets a grid (e.g., 3×4 = 12 compartments)

#### Step 2: Link Existing Burials to Compartments

Go to: **Admin → Compartment Manager**

1. Select a plot
2. Select a compartment
3. Search for the existing burial record
4. Assign it to that compartment
5. Repeat for all existing burials

#### Step 3: Add New Burials to Same Location

When you want to add another burial to the same plot:

1. Go to **Compartment Manager**
2. Select the plot
3. Select an available compartment
4. Click "Add Record"
5. Fill in the new decedent info
6. Assign burial level (Ground, First, Second, Third)
7. Save

The new burial will automatically use the same coordinates as the plot.

**Pros:**
- ✅ No manual coordinate copying
- ✅ Visual map showing which compartments are occupied
- ✅ Track capacity (e.g., "3/12 compartments filled")
- ✅ Supports burial levels (ground, 1st, 2nd, 3rd)
- ✅ Family plots management

**Cons:**
- Requires initial setup (one-time)

---

## Quick Start Script

To quickly enable the compartment system on your production site:

1. Go to: `https://yoursite.com/admin/create_plots_from_burials.php`
2. Review the unique plots found
3. Set default grid size (e.g., 3 rows × 4 columns)
4. Click "Create Plots with Compartments"
5. Done! Now use Compartment Manager to add new burials

---

## Future Enhancement Idea

We could add a "Quick Add to Same Location" feature on the Add Record page:

```
[Existing Records at Same Location]
┌─────────────────────────────────┐
│ ☐ John Doe (Died: 2020-05-15)  │
│ ☐ Jane Doe (Died: 2021-08-20)  │
└─────────────────────────────────┘
Select a record to copy its location →
```

When you select one, it auto-fills:
- Plot Number
- Latitude
- Longitude  
- Barangay

Then you just fill in the new decedent's info and save.

Would you like this feature added?

---

## Summary

**For now:** Use `/admin/create_plots_from_burials.php` to set up the compartment system, then use **Compartment Manager** for all multi-burial management.

**Benefit:** Proper tracking of who's buried where, at what level, with visual maps and capacity management.

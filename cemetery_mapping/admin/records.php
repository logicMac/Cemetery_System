<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$total = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get records
$stmt = $pdo->prepare("
    SELECT id, decedent_name, family_name, birth_date, death_date, plot_number, 
           barangay, is_fenced, photo, date_added 
    FROM burial_records 
    ORDER BY date_added DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$per_page, $offset]);
$records = $stmt->fetchAll();
?>

<?php require_once 'includes/sidebar.php'; ?>

<div class="data-table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">All Burial Records</h2>
        <a href="add-record.php" class="btn-primary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Record
        </a>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-grid">
            <div class="filter-group">
                <label>
                    <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </label>
                <div class="search-wrapper">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="searchBox" placeholder="Search by name, plot, or family..." onkeyup="filterTable()">
                </div>
            </div>
            
            <div class="filter-group">
                <label>
                    <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Barangay
                </label>
                <select id="barangayFilter" onchange="filterTable()">
                    <option value="">All Barangays</option>
                    <option value="Matinao">Matinao</option>
                    <option value="Poblacion">Poblacion</option>
                    <option value="San Isidro">San Isidro</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>
                    <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    Type
                </label>
                <select id="typeFilter" onchange="filterTable()">
                    <option value="">All Types</option>
                    <option value="premium">Premium (Fenced)</option>
                    <option value="standard">Standard</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn-reset" onclick="resetFilters()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset
                </button>
            </div>
        </div>
    </div>
    
    <table class="data-table" id="recordsTable">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Family</th>
                <th>Birth - Death</th>
                <th>Plot</th>
                <th>Barangay</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No records found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td>
                            <?php if ($record['photo']): ?>
                                <img src="../uploads/photos/<?php echo htmlspecialchars($record['photo'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: var(--glass-bg); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <svg style="width: 24px; height: 24px; color: var(--zinc-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($record['decedent_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($record['family_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php 
                            $birth = $record['birth_date'] ? date('Y', strtotime($record['birth_date'])) : '?';
                            $death = $record['death_date'] ? date('Y', strtotime($record['death_date'])) : '?';
                            echo "$birth - $death";
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($record['plot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($record['barangay'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($record['is_fenced']): ?>
                                <span class="badge-premium">Premium</span>
                            <?php else: ?>
                                <span class="badge-available">Standard</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-view" onclick="viewRecord(<?php echo $record['id']; ?>)" title="View">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                <button class="btn-icon btn-edit" onclick="editRecord(<?php echo $record['id']; ?>)" title="Edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button class="btn-icon btn-delete" onclick="deleteRecord(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['decedent_name'], ENT_QUOTES, 'UTF-8'); ?>')" title="Delete">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <button onclick="window.location.href='?page=<?php echo $page - 1; ?>'">Previous</button>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <button class="<?php echo $i === $page ? 'active' : ''; ?>" 
                        onclick="window.location.href='?page=<?php echo $i; ?>'">
                    <?php echo $i; ?>
                </button>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <button onclick="window.location.href='?page=<?php echo $page + 1; ?>'">Next</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script>
        function filterTable() {
            const searchInput = document.getElementById('searchBox');
            const barangayFilter = document.getElementById('barangayFilter');
            const typeFilter = document.getElementById('typeFilter');
            
            const searchValue = searchInput.value.toUpperCase();
            const barangayValue = barangayFilter.value.toUpperCase();
            const typeValue = typeFilter.value.toLowerCase();
            
            const table = document.getElementById('recordsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                
                if (cells.length === 0) continue;
                
                // Get cell values
                const name = cells[1].textContent || cells[1].innerText;
                const family = cells[2].textContent || cells[2].innerText;
                const plot = cells[4].textContent || cells[4].innerText;
                const barangay = cells[5].textContent || cells[5].innerText;
                const type = cells[6].textContent || cells[6].innerText;
                
                // Check search filter
                const searchText = (name + ' ' + family + ' ' + plot).toUpperCase();
                const matchesSearch = searchValue === '' || searchText.indexOf(searchValue) > -1;
                
                // Check barangay filter
                const matchesBarangay = barangayValue === '' || barangay.toUpperCase().indexOf(barangayValue) > -1;
                
                // Check type filter
                let matchesType = true;
                if (typeValue === 'premium') {
                    matchesType = type.toLowerCase().indexOf('premium') > -1;
                } else if (typeValue === 'standard') {
                    matchesType = type.toLowerCase().indexOf('standard') > -1;
                }
                
                // Show/hide row based on all filters
                if (matchesSearch && matchesBarangay && matchesType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
        
        function resetFilters() {
            document.getElementById('searchBox').value = '';
            document.getElementById('barangayFilter').value = '';
            document.getElementById('typeFilter').value = '';
            filterTable();
        }
        
        async function viewRecord(id) {
            try {
                const response = await fetch(`../api/get_record.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const record = data.record;
                    const photoHtml = record.photo 
                        ? `<img src="../uploads/photos/${record.photo}" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 16px;">`
                        : '';
                    
                    const content = `
                        <div>
                            ${photoHtml}
                            <h3 style="margin-bottom: 16px;">${record.decedent_name}</h3>
                            <p style="margin: 8px 0;"><strong>Family:</strong> ${record.family_name || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Birth Date:</strong> ${record.birth_date || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Death Date:</strong> ${record.death_date || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Plot Number:</strong> ${record.plot_number || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Barangay:</strong> ${record.barangay || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Type:</strong> ${record.is_fenced == 1 ? 'Premium/Fenced' : 'Standard'}</p>
                            ${record.memory_space ? `<p style="margin: 16px 0 8px 0;"><strong>Memory:</strong></p><p style="font-style: italic;">${record.memory_space}</p>` : ''}
                            <p style="margin: 16px 0 8px 0;"><strong>Coordinates:</strong> ${record.latitude}, ${record.longitude}</p>
                        </div>
                    `;
                    
                    themeUtils.showModal(content, 'Record Details');
                }
            } catch (error) {
                themeUtils.showAlert('Failed to load record', 'error');
            }
        }
        
        function editRecord(id) {
            window.location.href = `edit-record.php?id=${id}`;
        }
        
        function deleteRecord(id, name) {
            themeUtils.confirm(
                `Are you sure you want to delete the record for "${name}"? This action cannot be undone.`,
                async () => {
                    try {
                        const response = await fetch('../api/delete_record.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            themeUtils.showAlert('Record deleted successfully', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            themeUtils.showAlert(data.error || 'Failed to delete record', 'error');
                        }
                    } catch (error) {
                        themeUtils.showAlert('An error occurred', 'error');
                    }
                }
            );
        }
    </script>
</body>
</html>
    .filter-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 16px;
        align-items: end;
    }
    
    .filter-group-enhanced {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-group-enhanced label {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.7);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .filter-group-enhanced input,
    .filter-group-enhanced select {
        padding: 12px 16px;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        color: white;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .filter-group-enhanced input:focus,
    .filter-group-enhanced select:focus {
        outline: none;
        border-color: rgba(102, 126, 234, 0.5);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Records Grid */
    .records-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .record-card {
        background: rgba(10, 10, 20, 0.75);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 20px;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .record-card:hover {
        border-color: rgba(102, 126, 234, 0.3);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transform: translateY(-2px);
    }
    
    .record-photo {
        width: 100%;
        height: 200px;
        border-radius: 12px;
        object-fit: cover;
        margin-bottom: 16px;
    }
    
    .record-photo-placeholder {
        width: 100%;
        height: 200px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    
    .record-name {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .record-info {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        margin-bottom: 12px;
        line-height: 1.6;
    }
    
    .record-badges {
        display: flex;
        gap: 8px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-premium {
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.3);
    }
    
    .badge-standard {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    
    .record-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }
    
    .btn-icon-enhanced {
        flex: 1;
        padding: 10px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 0.85rem;
        color: white;
    }
    
    .btn-icon-enhanced:hover {
        background: rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.3);
    }
    
    .btn-icon-enhanced svg {
        width: 16px;
        height: 16px;
    }
    
    .btn-delete-enhanced:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: rgba(10, 10, 20, 0.75);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    
    .empty-state svg {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        opacity: 0.5;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .stats-overview {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .stat-box {
            padding: 16px;
        }
        
        .stat-number {
            font-size: 2rem;
        }
        
        .filter-row {
            grid-template-columns: 1fr;
        }
        
        .records-grid {
            grid-template-columns: 1fr;
        }
        
        .record-actions {
            flex-direction: column;
        }
    }
</style>


<!-- Page Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="margin: 0 0 8px 0; font-size: 2rem;">Burial Records</h2>
        <p style="margin: 0; color: rgba(255, 255, 255, 0.6); font-size: 0.95rem;">Manage all cemetery burial records</p>
    </div>
    <a href="add-record.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Add New Record
    </a>
</div>

<!-- Statistics Overview -->
<div class="stats-overview">
    <div class="stat-box <?php echo $status_filter === 'all' ? 'active' : ''; ?>" onclick="filterByType('all')">
        <div class="stat-number"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total Records</div>
    </div>
    <div class="stat-box <?php echo $status_filter === 'premium' ? 'active' : ''; ?>" onclick="filterByType('premium')">
        <div class="stat-number"><?php echo $stats['premium']; ?></div>
        <div class="stat-label">Premium Plots</div>
    </div>
    <div class="stat-box <?php echo $status_filter === 'standard' ? 'active' : ''; ?>" onclick="filterByType('standard')">
        <div class="stat-number"><?php echo $stats['standard']; ?></div>
        <div class="stat-label">Standard Plots</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar-enhanced">
    <form method="GET" action="records.php" id="filterForm">
        <div class="filter-row">
            <div class="filter-group-enhanced">
                <label>
                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </label>
                <input type="text" name="search" id="searchInput" placeholder="Name, family, or plot..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group-enhanced">
                <label>
                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    </svg>
                    Barangay
                </label>
                <select name="barangay" id="barangayFilter">
                    <option value="">All</option>
                    <option value="Matinao" <?php echo $barangay_filter === 'Matinao' ? 'selected' : ''; ?>>Matinao</option>
                    <option value="Poblacion" <?php echo $barangay_filter === 'Poblacion' ? 'selected' : ''; ?>>Poblacion</option>
                    <option value="San Isidro" <?php echo $barangay_filter === 'San Isidro' ? 'selected' : ''; ?>>San Isidro</option>
                    <option value="Other" <?php echo $barangay_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="filter-group-enhanced">
                <label>
                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    Type
                </label>
                <select name="type" id="typeFilter">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="premium" <?php echo $status_filter === 'premium' ? 'selected' : ''; ?>>Premium</option>
                    <option value="standard" <?php echo $status_filter === 'standard' ? 'selected' : ''; ?>>Standard</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-primary">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                </button>
                <?php if (!empty($search) || !empty($barangay_filter) || $status_filter !== 'all'): ?>
                <button type="button" class="btn-secondary" onclick="window.location.href='records.php'">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
function filterByType(type) {
    const form = document.getElementById('filterForm');
    document.getElementById('typeFilter').value = type;
    form.submit();
}
</script>

<!-- Records Grid -->
<?php if (empty($records)): ?>
    <div class="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 style="color: rgba(255, 255, 255, 0.8); margin-bottom: 12px;">No Records Found</h3>
        <p style="color: rgba(255, 255, 255, 0.5);">
            <?php if (!empty($search) || !empty($barangay_filter) || $status_filter !== 'all'): ?>
                No records match your search criteria. Try adjusting your filters.
            <?php else: ?>
                There are no burial records yet. Click "Add New Record" to create one.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <div class="records-grid">
        <?php foreach ($records as $record): ?>
            <div class="record-card">
                <?php if ($record['photo']): ?>
                    <img src="../uploads/photos/<?php echo htmlspecialchars($record['photo']); ?>" class="record-photo" alt="<?php echo htmlspecialchars($record['decedent_name']); ?>">
                <?php else: ?>
                    <div class="record-photo-placeholder">
                        <svg style="width: 60px; height: 60px; color: rgba(255, 255, 255, 0.3);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <div class="record-name"><?php echo htmlspecialchars($record['decedent_name']); ?></div>
                
                <div class="record-info">
                    <div style="margin-bottom: 6px;">
                        <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <?php echo htmlspecialchars($record['family_name'] ?? 'N/A'); ?>
                    </div>
                    <div style="margin-bottom: 6px;">
                        <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <?php 
                        $birth = $record['birth_date'] ? date('Y', strtotime($record['birth_date'])) : '?';
                        $death = $record['death_date'] ? date('Y', strtotime($record['death_date'])) : '?';
                        echo "$birth - $death";
                        ?>
                    </div>
                    <div style="margin-bottom: 6px;">
                        <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        Plot: <?php echo htmlspecialchars($record['plot_number'] ?? 'N/A'); ?>
                    </div>
                    <div>
                        <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <?php echo htmlspecialchars($record['barangay'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="record-badges">
                    <?php if ($record['is_fenced']): ?>
                        <span class="badge badge-premium">Premium</span>
                    <?php else: ?>
                        <span class="badge badge-standard">Standard</span>
                    <?php endif; ?>
                </div>
                
                <div class="record-actions">
                    <button class="btn-icon-enhanced" onclick="viewRecord(<?php echo $record['id']; ?>)">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View
                    </button>
                    <button class="btn-icon-enhanced" onclick="editRecord(<?php echo $record['id']; ?>)">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </button>
                    <button class="btn-icon-enhanced btn-delete-enhanced" onclick="deleteRecord(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['decedent_name'], ENT_QUOTES, 'UTF-8'); ?>')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 30px; flex-wrap: wrap;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&barangay=<?php echo urlencode($barangay_filter); ?>&type=<?php echo urlencode($status_filter); ?>" class="btn-secondary" style="padding: 10px 16px;">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
            </a>
        <?php endif; ?>
        
        <?php 
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&barangay=<?php echo urlencode($barangay_filter); ?>&type=<?php echo urlencode($status_filter); ?>" 
               class="<?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>" 
               style="padding: 10px 16px; min-width: 44px; text-align: center;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&barangay=<?php echo urlencode($barangay_filter); ?>&type=<?php echo urlencode($status_filter); ?>" class="btn-secondary" style="padding: 10px 16px;">
                Next
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script>
        async function viewRecord(id) {
            try {
                const response = await fetch(`../api/get_record.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const record = data.record;
                    const photoHtml = record.photo 
                        ? `<img src="../uploads/photos/${record.photo}" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 16px;">`
                        : '';
                    
                    const content = `
                        <div>
                            ${photoHtml}
                            <h3 style="margin-bottom: 16px;">${record.decedent_name}</h3>
                            <p style="margin: 8px 0;"><strong>Family:</strong> ${record.family_name || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Birth Date:</strong> ${record.birth_date || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Death Date:</strong> ${record.death_date || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Plot Number:</strong> ${record.plot_number || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Barangay:</strong> ${record.barangay || 'N/A'}</p>
                            <p style="margin: 8px 0;"><strong>Type:</strong> ${record.is_fenced == 1 ? 'Premium/Fenced' : 'Standard'}</p>
                            ${record.memory_space ? `<p style="margin: 16px 0 8px 0;"><strong>Memory:</strong></p><p style="font-style: italic;">${record.memory_space}</p>` : ''}
                            <p style="margin: 16px 0 8px 0;"><strong>Coordinates:</strong> ${record.latitude}, ${record.longitude}</p>
                        </div>
                    `;
                    
                    themeUtils.showModal(content, 'Record Details');
                }
            } catch (error) {
                themeUtils.showAlert('Failed to load record', 'error');
            }
        }
        
        function editRecord(id) {
            window.location.href = `edit-record.php?id=${id}`;
        }
        
        function deleteRecord(id, name) {
            themeUtils.confirm(
                `Are you sure you want to delete the record for "${name}"? This action cannot be undone.`,
                async () => {
                    try {
                        const response = await fetch('../api/delete_record.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            themeUtils.showAlert('Record deleted successfully', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            themeUtils.showAlert(data.error || 'Failed to delete record', 'error');
                        }
                    } catch (error) {
                        themeUtils.showAlert('An error occurred', 'error');
                    }
                }
            );
        }
    </script>
</body>
</html>

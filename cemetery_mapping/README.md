# Matinao Memorial Cemetery Mapping and Management System

## 🏗️ System Overview

A complete, production-ready cemetery management system with dual interfaces:
- **Visitor Portal**: Public-facing interactive map with AI assistant
- **Admin Panel**: Comprehensive management dashboard

## 📋 Technical Stack

### Backend
- **Language**: PHP 7.4+ (Native, no frameworks)
- **Database**: MySQL 8.0+ with InnoDB engine
- **Session Management**: Native PHP sessions (30-minute timeout)
- **Security**: bcrypt password hashing, prepared statements, XSS protection

### Frontend
- **CSS Framework**: Tailwind CSS 3.x (CDN)
- **JavaScript**: Vanilla ES6+
- **Typography**: Google Fonts Poppins (300, 400, 500, 600, 700)
- **Icons**: Heroicons (inline SVG)

### Mapping
- **Core**: Leaflet.js 1.9.4
- **Extensions**: Leaflet-Rotate 0.2.8, Leaflet.Fullscreen 2.4.0
- **Clustering**: Leaflet.markercluster
- **Tiles**: Google Satellite (default), Google Hybrid, Esri World Imagery, OSM

### AI Integration
- **Provider**: Groq API
- **Model**: llama-3.1-70b-versatile
- **Transport**: Native PHP cURL

## 🎨 Design System

### Color Palette
- Background: Pure Black (#000000)
- Glass Cards: rgba(255, 255, 255, 0.05) with 10px blur
- Primary Gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
- Available Plots: Green (#22c55e)
- Premium/Fenced: Gold (#fbbf24)
- Secondary Text: Zinc-400 (#a1a1aa)

### Transitions
- All interactive elements: 0.3s ease
- Smooth scrolling: easeInOutCubic timing function

## 📁 Directory Structure

```
cemetery_mapping/
├── admin/                  # Admin panel pages
│   ├── includes/          # Header and sidebar
│   ├── dashboard.php      # ✅ Created
│   ├── add-record.php     # ⚠️ To be created
│   ├── records.php        # ⚠️ To be created
│   ├── map-view.php       # ⚠️ To be created
│   ├── available-plots.php # ⚠️ To be created
│   ├── statistics.php     # ⚠️ To be created
│   ├── reports.php        # ⚠️ To be created
│   ├── assistant.php      # ⚠️ To be created
│   ├── settings.php       # ⚠️ To be created
│   ├── login.php          # ✅ Created
│   └── logout.php         # ✅ Created
├── api/                   # API endpoints
│   ├── add_record.php     # ⚠️ To be created
│   ├── update_record.php  # ⚠️ To be created
│   ├── delete_record.php  # ⚠️ To be created
│   ├── get_record.php     # ⚠️ To be created
│   ├── get_all_records.php # ✅ Created
│   ├── get_recent_records.php # ⚠️ To be created
│   ├── search.php         # ✅ Created
│   ├── get_barangays.php  # ⚠️ To be created
│   ├── get_statistics.php # ⚠️ To be created
│   ├── add_available_plot.php # ⚠️ To be created
│   ├── get_available_plots.php # ✅ Created
│   ├── delete_available_plot.php # ⚠️ To be created
│   ├── save_plot_grid.php # ⚠️ To be created
│   ├── get_plot_grid.php  # ⚠️ To be created
│   ├── assistant_api.php  # ⚠️ To be created
│   ├── visitor_assistant.php # ✅ Created
│   └── check_email.php    # ✅ Created
├── assets/
│   ├── css/
│   │   ├── admin.css      # ✅ Created
│   │   ├── style.css      # ✅ Created
│   │   ├── theme.css      # ✅ Created
│   │   └── smooth-scroll.css # ✅ Created
│   ├── js/
│   │   ├── admin.js       # ⚠️ To be created
│   │   ├── visitor.js     # ✅ Created
│   │   ├── smooth-scroll.js # ✅ Created
│   │   └── theme.js       # ✅ Created
│   └── images/            # Logo files to be added
├── config/
│   ├── database.php       # ✅ Created
│   └── groq_config.php    # ✅ Created
├── uploads/
│   ├── photos/            # Burial photos
│   └── plots/             # Plot photos
├── visitor/
│   ├── login.php          # ✅ Created
│   ├── register.php       # ✅ Created
│   ├── dashboard.php      # ✅ Created
│   └── logout.php         # ✅ Created
├── index.php              # ✅ Created
├── .htaccess              # ✅ Created
├── database.sql           # ✅ Created
└── README.md              # ✅ This file
```

## 🚀 Installation Instructions

### 1. Database Setup
```sql
-- Import the database schema
mysql -u root -p < database.sql

-- Or use phpMyAdmin to import database.sql
```

### 2. Configuration
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cemetery_mapping');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Edit `config/groq_config.php`:
```php
define('GROQ_API_KEY', 'your_actual_groq_api_key_here');
```

### 3. File Permissions
```bash
chmod 755 uploads/photos
chmod 755 uploads/plots
```

### 4. Default Credentials
- **Admin**: username: `admin`, password: `admin123`
- **Visitor**: Register through visitor/register.php

## 🔐 Security Features

1. **SQL Injection Prevention**: All queries use prepared statements
2. **XSS Protection**: All output sanitized with htmlspecialchars()
3. **Password Security**: bcrypt hashing with PASSWORD_DEFAULT
4. **Session Security**: 30-minute timeout, secure session handling
5. **File Upload Validation**: MIME type checking, 5MB limit
6. **Directory Protection**: .htaccess blocks PHP execution in uploads/

## 🗺️ Map Configuration

### Cemetery Coordinates
- **Center**: 6.18344118743717, 125.08457146469357
- **Default Zoom**: 17
- **Zoom Range**: 10-20

### Marker Colors
- **Blue**: Standard burial
- **Gold**: Premium/fenced plot
- **Green**: Available plot
- **Red**: Search result
- **Blue (pulsing)**: User location

## 🤖 AI Assistant Features

### Visitor Assistant
- Find burial locations
- Provide directions
- Answer cemetery questions
- Auto-navigation with [NAV_TO: lat, lng] commands

### Admin Assistant
- System statistics
- Analytics queries
- Operational reports

## 📊 Database Schema

### Tables
1. **admin_users**: Admin credentials
2. **visitors**: Public user accounts
3. **burial_records**: Deceased person information
4. **available_plots**: Available burial locations
5. **plot_compartments**: Grid subdivisions within plots
6. **visitor_activity_log**: Visitor activity tracking

## 🎯 Key Features

### Visitor Portal
- ✅ Interactive Leaflet map with multiple tile layers
- ✅ Real-time GPS tracking and navigation
- ✅ Search by name, plot, family, or barangay
- ✅ AI-powered assistant
- ✅ Marker clustering for performance
- ✅ Glass morphism UI design
- ✅ Responsive mobile layout

### Admin Panel
- ✅ Dashboard with statistics
- ⚠️ Add/Edit/Delete burial records
- ⚠️ Map-based coordinate picker
- ⚠️ Grid builder for multi-compartment plots
- ⚠️ Photo upload management
- ⚠️ CSV/PDF report generation
- ⚠️ Database backup functionality

## 📝 Remaining Tasks

### High Priority
1. **admin/add-record.php**: Form with map picker for adding burial records
2. **admin/records.php**: Data table with edit/delete actions
3. **admin/available-plots.php**: Plot management with grid builder
4. **api/add_record.php**: Handle record creation with file upload
5. **api/update_record.php**: Handle record updates
6. **api/delete_record.php**: Handle record deletion
7. **assets/js/admin.js**: Admin panel JavaScript utilities

### Medium Priority
8. **admin/map-view.php**: Full-screen map view for admins
9. **admin/statistics.php**: Detailed analytics and charts
10. **admin/reports.php**: Report generation (CSV/PDF)
11. **admin/assistant.php**: Admin AI assistant interface
12. **admin/settings.php**: System configuration
13. **api/assistant_api.php**: Admin AI assistant backend

### Low Priority
14. Logo files (SVG format)
15. Additional barangay management
16. Visitor activity analytics
17. Email notifications

## 🧪 Testing Checklist

- [ ] Database connection successful
- [ ] Admin login works
- [ ] Visitor registration works
- [ ] Map loads with correct center
- [ ] Markers display correctly
- [ ] Search functionality works
- [ ] AI assistant responds
- [ ] GPS navigation works
- [ ] File uploads work
- [ ] Session timeout works
- [ ] Mobile responsive design

## 📱 Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 🔧 Troubleshooting

### Map not loading
- Check internet connection (CDN resources)
- Verify Leaflet.js loaded correctly
- Check browser console for errors

### Database errors
- Verify MySQL service is running
- Check database credentials in config/database.php
- Ensure database was imported correctly

### AI Assistant not working
- Verify Groq API key is set
- Check API key has sufficient credits
- Review PHP error logs

### File uploads failing
- Check uploads/ directory permissions
- Verify PHP upload_max_filesize setting
- Check file MIME type validation

## 📄 License

Proprietary - Matinao Memorial Cemetery Management System

## 👥 Support

For technical support, contact the system administrator.

---

**Status**: Core system operational, additional admin features pending implementation
**Version**: 1.0.0
**Last Updated**: June 2026

# User Menu Permission System

## Overview

The SIAPKAK system now includes a granular menu permission system that allows administrators to control which menus individual users can access. This provides fine-grained access control beyond just admin/user roles.

## How It Works

### Database Schema

The system uses a new table `user_menu_access`:

```sql
CREATE TABLE user_menu_access (
    user_id INT NOT NULL,
    menu_id INT NOT NULL,
    can_access BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_user_menu (user_id, menu_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
)
```

### Permission Logic

1. **Admin Users**: Always have access to ALL menus (bypass permission checks)
2. **Non-Admin Users**: Access is determined by the `user_menu_access` table
   - If a record exists with `can_access = 1`, user can access the menu
   - If a record exists with `can_access = 0`, user is denied access
   - If no record exists, access is denied by default

### Default Permissions

When the system is initialized, all non-admin users are granted access to **Dashboard only**. Administrators must explicitly grant access to other menus.

## Using the Permission System

### For Administrators

#### Accessing User Permissions

1. Navigate to **User Management** page
2. Find the user you want to manage
3. Click the **key icon** (🔑) next to the user's name
4. A modal will open showing all available menus

#### Granting/Revoking Permissions

1. In the permission modal, you'll see checkboxes for each menu:
   - ✅ Checked = User has access
   - ⬜ Unchecked = User is denied access

2. Check/uncheck the menus you want to grant/revoke

3. Click **Save Permissions** to apply changes

4. The user will immediately see the updated menu list on their next page load

#### Important Notes

- **Admin users do not have a permission button** - they always have full access
- Changes take effect immediately
- Users logged in will see updated menus after refreshing the page

## API Endpoints

### Get User Menu Permissions

```http
GET /api/users/menu-access?user_id={id}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "title": "Dashboard",
      "route": "dashboard",
      "icon": "fas fa-home",
      "can_access": 1
    },
    {
      "id": 2,
      "title": "Monitoring",
      "route": "monitoring",
      "icon": "fas fa-broadcast-tower",
      "can_access": 0
    }
  ]
}
```

### Update User Menu Permissions

```http
PUT /api/users/menu-access
Content-Type: application/json

{
  "user_id": 2,
  "permissions": {
    "12": true,
    "2": true,
    "3": false,
    "4": false,
    "5": false
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Permissions updated successfully"
}
```

## Backend Implementation

### PermissionHelper.php

The `PermissionHelper` class handles permission checks:

```php
// Check if user has access to a specific route
PermissionHelper::hasAccess('monitoring'); // Returns true/false

// Get list of menus accessible to current user
$menus = PermissionHelper::getAccessibleMenus(); // Returns filtered menu array
```

### How It's Used

1. **Sidebar Menu Filtering**
   - `sidebar.php` calls `getAccessibleMenus()`
   - Only shows menus where user has permission

2. **Route Protection**
   - Each protected page calls `PermissionHelper::requireAccess('route_name')`
   - Redirects to 403 error if user doesn't have permission

## Use Cases

### Scenario 1: Read-Only User
Grant a user access to view reports but not modify data:
- ✅ Dashboard
- ✅ Monitoring
- ✅ Laporan (Reports)
- ⬜ Manajemen (Management)
- ⬜ Pengaturan (Settings)

### Scenario 2: Data Entry User
Grant a user access to add readings but not manage users:
- ✅ Dashboard
- ✅ Monitoring (to add readings)
- ⬜ Laporan
- ⬜ Manajemen
- ⬜ Pengaturan

### Scenario 3: Report Viewer Only
Grant a user access to only view the dashboard:
- ✅ Dashboard
- ⬜ All others

## Testing

Run the test script to verify permissions:

```bash
php test_permission_system.php
```

This will show:
- User details (name, email, role)
- Menu access status for each menu
- List of accessible menus

## Database Queries

### Check User Permissions

```sql
SELECT 
    u.name, 
    m.title, 
    uma.can_access 
FROM user_menu_access uma
JOIN users u ON uma.user_id = u.id
JOIN menus m ON uma.menu_id = m.id
WHERE u.id = 2
ORDER BY m.id;
```

### Grant Access to All Menus for a User

```sql
INSERT INTO user_menu_access (user_id, menu_id, can_access)
SELECT 2, id, 1 FROM menus WHERE is_active = 1 AND parent_id IS NULL;
```

### Revoke All Access for a User

```sql
UPDATE user_menu_access 
SET can_access = 0 
WHERE user_id = 2;
```

## Troubleshooting

### User can't see any menus

1. Check if permissions are set:
   ```sql
   SELECT * FROM user_menu_access WHERE user_id = 2;
   ```

2. Grant default access:
   ```sql
   INSERT INTO user_menu_access (user_id, menu_id, can_access)
   SELECT 2, id, 1 FROM menus WHERE route = 'dashboard';
   ```

### Admin can't access User Management

- Admin users should always have full access
- Check if the user's role is correctly set to 'admin' in the database

### Changes not reflecting immediately

- Clear browser cache
- Log out and log back in
- Check browser console for JavaScript errors

## Security Considerations

1. **Always verify on backend**: Frontend hiding of menus is not security - backend must verify permissions
2. **Direct URL access**: Users can try to access pages directly via URL. Each page must call `PermissionHelper::requireAccess()`
3. **API endpoints**: All API endpoints should check permissions before processing requests

## Future Enhancements

Potential improvements to the permission system:

- [ ] Permission groups/presets (e.g., "Read Only", "Editor", "Manager")
- [ ] Audit log of permission changes
- [ ] Bulk permission assignment
- [ ] Time-based permissions (temporary access)
- [ ] Permission inheritance from user groups

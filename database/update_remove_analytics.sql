-- =====================================================
-- Remove Analytics Menu and Merge with Dashboard
-- =====================================================

-- Delete Analytics menu and its permissions
DELETE FROM `user_menu_access` WHERE menu_id IN (SELECT id FROM menus WHERE route = 'analytics');
DELETE FROM `menus` WHERE route = 'analytics';

-- Update Dashboard menu structure
-- Remove parent Dashboard if it exists and keep only the main Dashboard link
DELETE FROM `menus` WHERE title = 'Dashboard' AND url = '#' AND parent_id IS NULL;

-- Update Dashboard menu to be a single item (no children)
UPDATE `menus` 
SET 
    parent_id = NULL,
    title = 'Dashboard',
    icon = 'fas fa-home',
    url = '/siapkak/dashboard',
    route = 'dashboard',
    order_index = 1,
    description = 'Dashboard overview with analytics'
WHERE route = 'dashboard';

-- Update parent_id for Monitoring children (was 4, now should be 2)
UPDATE `menus` 
SET parent_id = 2 
WHERE parent_id IN (SELECT id FROM (SELECT id FROM menus WHERE route = 'monitoring' LIMIT 1) AS temp);

-- Update parent_id for Management children (was 8, now should be 6)
UPDATE `menus` 
SET parent_id = 6 
WHERE parent_id IN (SELECT id FROM (SELECT id FROM menus WHERE title = 'Manajemen' AND url = '#' LIMIT 1) AS temp);

-- Verify changes
SELECT id, parent_id, title, route, url, order_index 
FROM menus 
ORDER BY order_index, parent_id, id;

<?php
/**
 * Breadcrumb Component
 * Usage: include with $breadcrumbs array
 * Example: $breadcrumbs = [['label' => 'Dashboard', 'url' => '/siapkak/dashboard'], ['label' => 'Reports']];
 */
if (!isset($breadcrumbs) || !is_array($breadcrumbs)) {
    $breadcrumbs = [['label' => 'Dashboard', 'url' => '/siapkak/dashboard']];
}
?>
<nav class="mb-6">
    <ol class="flex items-center space-x-2 text-sm text-gray-600">
        <li>
            <a href="/siapkak/dashboard" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
        </li>
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <li class="flex items-center">
                <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
                <?php if (isset($crumb['url']) && $index < count($breadcrumbs) - 1): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="hover:text-blue-600 transition">
                        <?= htmlspecialchars($crumb['label']) ?>
                    </a>
                <?php else: ?>
                    <span class="text-gray-800 font-semibold">
                        <?= htmlspecialchars($crumb['label']) ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

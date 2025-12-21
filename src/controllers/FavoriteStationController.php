<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Config\Auth;
use PDOException;
use App\Models\FavoriteStation;

class FavoriteStationController
{
    private FavoriteStation $favoriteModel;
    private int $userId;

    public function __construct()
    {
        $db = Database::getInstance()->getConnection();
        $this->favoriteModel = new FavoriteStation($db);
        
        // Support both session and JWT
        $currentUser = Auth::getCurrentUser();
        $this->userId = $currentUser->sub ?? 0;
        
        // Ensure user is logged in
        if ($this->userId === 0) {
            Response::error('Unauthorized', null, 401);
            exit;
        }
        
        // Check user role - only 'user' role can access favorites
        $userRole = $_SESSION['user_role'] ?? null;
        if ($userRole !== 'user') {
            Response::error('This feature is only available for users', null, 403);
            exit;
        }
    }

    /**
     * Get all user's favorite stations
     * GET /api/favorites
     */
    public function index(): void
    {
        try {
            $favorites = $this->favoriteModel->getUserFavorites($this->userId);
            
            Response::success([
                'favorites' => $favorites,
                'total' => count($favorites)
            ], 'Favorites retrieved successfully');
        } catch (\Exception $e) {
            Response::error('Failed to retrieve favorites: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get favorite stations with latest readings
     * GET /api/favorites/with-readings
     */
    public function withReadings(): void
    {
        try {
            $limit = $_GET['limit'] ?? 10;
            $favorites = $this->favoriteModel->getUserFavoritesWithReadings($this->userId, (int)$limit);
            
            Response::success([
                'favorites' => $favorites,
                'total' => count($favorites)
            ], 'Favorites with readings retrieved successfully');
        } catch (PDOException $e) {
            // If readings table missing, fallback to favorites without readings instead of 500
            if ($e->getCode() === '42S02') { // table not found
                $fallback = $this->favoriteModel->getUserFavorites($this->userId);
                Response::success([
                    'favorites' => $fallback,
                    'total' => count($fallback)
                ], 'Favorites retrieved (no readings table)');
                return;
            }
            Response::error('Failed to retrieve favorites: ' . $e->getMessage(), null, 500);
        } catch (\Exception $e) {
            Response::error('Failed to retrieve favorites: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Add station to favorites
     * POST /api/favorites
     * Body: { station_id: int, nickname?: string }
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['station_id'])) {
                Response::error('Station ID is required', null, 400);
                return;
            }

            $stationId = (int)$data['station_id'];
            $nickname = $data['nickname'] ?? null;

            // Check if already favorited
            if ($this->favoriteModel->isFavorite($this->userId, $stationId)) {
                Response::error('Station is already in your favorites', null, 400);
                return;
            }

            $favoriteId = $this->favoriteModel->addFavorite($this->userId, $stationId, $nickname);

            if ($favoriteId) {
                Response::success([
                    'favorite_id' => $favoriteId,
                    'station_id' => $stationId
                ], 'Station added to favorites', 201);
            } else {
                Response::error('Failed to add station to favorites', null, 500);
            }
        } catch (\Exception $e) {
            Response::error('Failed to add favorite: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove station from favorites
     * DELETE /api/favorites/{station_id}
     */
    public function destroy(int $stationId): void
    {
        try {
            $success = $this->favoriteModel->removeFavorite($this->userId, $stationId);

            if ($success) {
                Response::success([
                    'station_id' => $stationId
                ], 'Station removed from favorites');
            } else {
                Response::error('Failed to remove station from favorites', null, 500);
            }
        } catch (\Exception $e) {
            Response::error('Failed to remove favorite: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Check if station is favorited
     * GET /api/favorites/check/{station_id}
     */
    public function check(int $stationId): void
    {
        try {
            $isFavorite = $this->favoriteModel->isFavorite($this->userId, $stationId);
            
            Response::success([
                'station_id' => $stationId,
                'is_favorite' => $isFavorite
            ], 'Check completed');
        } catch (\Exception $e) {
            Response::error('Failed to check favorite status: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user's favorite station IDs (for bulk check)
     * GET /api/favorites/ids
     */
    public function getFavoriteIds(): void
    {
        try {
            $favoriteIds = $this->favoriteModel->getUserFavoriteIds($this->userId);
            
            Response::success([
                'station_ids' => $favoriteIds,
                'total' => count($favoriteIds)
            ], 'Favorite IDs retrieved');
        } catch (\Exception $e) {
            Response::error('Failed to retrieve favorite IDs: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update favorite nickname
     * PUT /api/favorites/{favorite_id}
     * Body: { nickname: string }
     */
    public function update(int $favoriteId): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $nickname = $data['nickname'] ?? null;

            $success = $this->favoriteModel->updateNickname($favoriteId, $this->userId, $nickname);

            if ($success) {
                Response::success([
                    'favorite_id' => $favoriteId,
                    'nickname' => $nickname
                ], 'Favorite nickname updated');
            } else {
                Response::error('Failed to update nickname or favorite not found', null, 500);
            }
        } catch (\Exception $e) {
            Response::error('Failed to update favorite: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get favorites count
     * GET /api/favorites/count
     */
    public function count(): void
    {
        try {
            $count = $this->favoriteModel->getFavoritesCount($this->userId);
            
            Response::success([
                'count' => $count
            ], 'Count retrieved');
        } catch (\Exception $e) {
            Response::error('Failed to get count: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Toggle favorite (add if not exists, remove if exists)
     * POST /api/favorites/toggle
     * Body: { station_id: int }
     */
    public function toggle(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['station_id'])) {
                Response::error('Station ID is required', null, 400);
                return;
            }

            $stationId = (int)$data['station_id'];
            $isFavorite = $this->favoriteModel->isFavorite($this->userId, $stationId);

            if ($isFavorite) {
                // Remove from favorites
                $success = $this->favoriteModel->removeFavorite($this->userId, $stationId);
                $action = 'removed';
                $newStatus = false;
            } else {
                // Add to favorites
                $nickname = $data['nickname'] ?? null;
                $success = $this->favoriteModel->addFavorite($this->userId, $stationId, $nickname);
                $action = 'added';
                $newStatus = true;
            }

            if ($success) {
                Response::success([
                    'station_id' => $stationId,
                    'is_favorite' => $newStatus,
                    'action' => $action
                ], "Station $action");
            } else {
                Response::error('Failed to toggle favorite', null, 500);
            }
        } catch (\Exception $e) {
            Response::error('Failed to toggle favorite: ' . $e->getMessage(), null, 500);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\YearDatabaseService;

class BaseModel extends Model
{
    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        // Lấy YearDatabaseService
        $yearService = app(YearDatabaseService::class);
        
        // Lấy connection name dựa trên năm đang xem
        $connectionName = $yearService->getConnection();
        
        // Set connection cho model này
        $this->connection = $connectionName;
        
        return parent::getConnection();
    }
}

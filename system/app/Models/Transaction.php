<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function PHPUnit\Framework\isArray;

class Transaction extends Model
{
    public const TYPE_NORMAL = 'normal';
    public const TYPE_RETURN = 'return';
    public const TYPE_LIST = [
        self::TYPE_NORMAL,
        self::TYPE_RETURN
    ];
    public const UNIT_MT = 'mt';
    public const UNIT_TON = 'ton';
    public const UNIT_CFT = 'cft';
    public const UNIT_KG = 'kg';
    public const UNIT_OTHER = 'other';
    public const UNIT_LIST = [
        self::UNIT_MT,
        self::UNIT_TON,
        self::UNIT_CFT,
        self::UNIT_KG,
        self::UNIT_OTHER
    ];
    protected $fillable = [
        "product_id",
        "loading_point_id",
        "loading_vehicle_id",
        "loading_date",
        "loading_rate",
        "loading_quantity",
        "unloading_point_id",
        "unloading_vehicle_id",
        "unloading_date",
        "unloading_rate",
        "unloading_quantity",
        "do_number",
        "challan_number",
        "txn_type",
        "transport_expense",
        "loading_driver_id",
        "unloading_driver_id",
        "recorder_id",
        "updater_id",
    ];

    protected $appends = ['is_sold'];

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function loadingPoint(): BelongsTo {
        return $this->belongsTo(Client::class, 'loading_point_id', 'id');
    }

    public function unloadingPoint(): BelongsTo {
        return $this->belongsTo(Client::class, 'unloading_point_id', 'id');
    }

    public function loadingVehicle(): BelongsTo {
        return $this->belongsTo(Vehicle::class, 'loading_vehicle_id', 'id');
    }

    public function unloadingVehicle(): BelongsTo {
        return $this->belongsTo(Vehicle::class, 'unloading_vehicle_id', 'id');
    }

    public function loadingDriver(): BelongsTo {
        return $this->belongsTo(User::class,'loading_driver_id','id');
    }
    public function unLoadingDriver(): BelongsTo {
        return $this->belongsTo(User::class,'unloading_driver_id','id');
    }

    /**
     * Scope to check if the transaction is sold.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsSold($query)
    {
        return $query->whereNotNull('unloading_point_id')
                     ->whereNotNull('unloading_vehicle_id')
                     ->whereNotNull('unloading_date');
    }

    /**
     * Accessor to check if the transaction is sold.
     *
     * @return bool
     */
    public function getIsSoldAttribute()
    {
        return !is_null($this->unloading_point_id) &&
               !is_null($this->unloading_vehicle_id) &&
               !is_null($this->unloading_date);
    }

    /**
     * Scope to search across transaction fields, product name,
     * client names (loading/unloading), and vehicle data.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchKeyword($query, string $keyword) {
        return $query->where(function ($q) use ($keyword) {
            $q->where('do_number', 'like', '%' . $keyword . '%')
              ->orWhere('challan_number', 'like', '%' . $keyword . '%')
              ->orWhereHas('product', function ($q2) use ($keyword) {
                  $q2->where('name', 'like', '%' . $keyword . '%');
              })
              ->orWhereHas('loadingPoint', function ($q2) use ($keyword) {
                  $q2->where('name', 'like', '%' . $keyword . '%');
              })
              ->orWhereHas('unloadingPoint', function ($q2) use ($keyword) {
                  $q2->where('name', 'like', '%' . $keyword . '%');
              })
              // Search in loading vehicle data.
              ->orWhereHas('loadingVehicle', function ($q2) use ($keyword) {
                  $q2->where('number', 'like', '%' . $keyword . '%')
                     ->orWhere('type', 'like', '%' . $keyword . '%');
              })
              // Search in unloading vehicle data.
              ->orWhereHas('unloadingVehicle', function ($q2) use ($keyword) {
                  $q2->where('number', 'like', '%' . $keyword . '%')
                     ->orWhere('type', 'like', '%' . $keyword . '%');
              })
              // Search in loading driver data.
              ->orWhereHas('loadingDriver', function ($q2) use ($keyword) {
                  $q2->where('name', 'like', '%' . $keyword . '%');
              })
              // Search in unloading driver data.
              ->orWhereHas('unLoadingDriver', function ($q2) use ($keyword) {
                  $q2->where('name', 'like', '%' . $keyword . '%');
              });
        });
    }

    /**
     * Optional scope if you want to search by vehicle separately.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $vehicleTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchVehicle($query, string $vehicleTerm) {
        return $query->where(function ($q) use ($vehicleTerm) {
            $q->orWhereHas('loadingVehicle', function ($q2) use ($vehicleTerm) {
                $q2->where('number', 'like', '%' . $vehicleTerm . '%')
                   ->orWhere('type', 'like', '%' . $vehicleTerm . '%');
            })->orWhereHas('unloadingVehicle', function ($q2) use ($vehicleTerm) {
                $q2->where('number', 'like', '%' . $vehicleTerm . '%')
                   ->orWhere('type', 'like', '%' . $vehicleTerm . '%');
            });
        });
    }

    /**
     * Scope to filter transactions by client size.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $clientSize
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByClientSize($query, array $clientSizes,string $type='loading') {
        return $query->where(function ($q) use ($clientSizes,$type) {
            if($type === 'loading'){
                $q->whereHas('loadingPoint', function ($q2) use ($clientSizes) {
                    if(isArray($clientSizes)) $q2->whereIn('client_size',$clientSizes);
                    else $q2->where('client_size', $clientSizes);
                });
            }else{
                $q->whereHas('unloadingPoint', function ($q2) use ($clientSizes) {
                    if(isArray($clientSizes)) $q2->whereIn('client_size',$clientSizes);
                    else $q2->where('client_size', $clientSizes);
                });
            }
        });
    }

    /**
     * Scope to filter transactions by transaction type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $txnType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByTxnType($query, string $txnType) {
        return $query->where('txn_type', $txnType);
    }

    /**
     * Scope to filter transactions by loading date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $from
     * @param string|null $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByDateRange($query, $type='loading', $from = null, $to = null) {
        if($type === 'loading'){
            if ($from) $query->whereDate('loading_date', '>=', $from);
            if ($to) $query->whereDate('loading_date', '<=', $to);
        }
        if($type === 'unloading'){
            if ($from) $query->whereDate('unloading_date', '>=', $from);
            if ($to) $query->whereDate('unloading_date', '<=', $to);
        }
        return $query;
    }
}

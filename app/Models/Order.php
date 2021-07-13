<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Order extends Model
{
    use HasFactory;

    const REFUND_STATUS_PENDING = 'pending';
    const REFUND_STATUS_APPLIED = 'applied';
    const REFUND_STATUS_PROCESSING = 'processing';
    const REFUND_STATUS_SUCCESS = 'success';
    const REFUND_STATUS_FAILED = 'failed';

    const SHIP_STATUS_PENDING = 'pending';
    const SHIP_STATUS_DELIVERED = 'delivered';
    const SHIP_STATUS_RECEIVED = 'received';

    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING    => 'No hay reembolso',
        self::REFUND_STATUS_APPLIED    => 'Reembolso requerido',
        self::REFUND_STATUS_PROCESSING => 'Reembolso',
        self::REFUND_STATUS_SUCCESS    => 'Reembolso con éxito',
        self::REFUND_STATUS_FAILED     => 'Reembolso fallido',
    ];

    public static $shipStatusMap = [
        self::SHIP_STATUS_PENDING   => 'No enviado',
        self::SHIP_STATUS_DELIVERED => 'Enviado',
        self::SHIP_STATUS_RECEIVED  => 'Recibió',
    ];

    protected $fillable = [
        'no',
        'address',
        'total_amount',
        'remark',
        'paid_at',
        'payment_method',
        'payment_no',
        'refund_status',
        'refund_no',
        'closed',
        'reviewed',
        'ship_status',
        'ship_data',
        'extra',
    ];

    protected $casts = [
        'closed'    => 'boolean',
        'reviewed'  => 'boolean',
        'address'   => 'json',
        'ship_data' => 'json',
        'extra'     => 'json',
    ];

    protected $dates = [
        'paid_at',
    ];

    protected static function boot()
    {
        parent::boot();
        //  Escuche los eventos de creación de modelos y active antes de escribir en la base de datos
        static::creating(function ($model) {
            // Si el campo no del modelo está vacío
            if (!$model->no) {
                // Llame a findAvailableNo para generar el número de serie del pedido
                $model->no = static::findAvailableNo();
                // Si la generación falla, se terminará la creación de la orden.
                if (!$model->no) {
                    return false;
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function findAvailableNo()
    {
        // Prefijo del número de serie del pedido
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++) {
            // Genere 6 dígitos aleatoriamente
            $no = $prefix.str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            // Determina si ya existe
            if (!static::query()->where('no', $no)->exists()) {
                return $no;
            }
        }
        \Log::warning('find order no failed');

        return false;
    }

    public static function getAvailableRefundNo()
    {
        do {
            //La clase Uuid se puede utilizar para generar cadenas con una alta probabilidad de que no se repitan
            $no = Uuid::uuid4()->getHex();
            //Para evitar la repetición, consultamos en la base de datos después de la generación para ver si ya existe el mismo número de orden de reembolso.
        } while (self::query()->where('refund_no', $no)->exists());

        return $no;
    }

    public function couponCode()
    {
        return $this->belongsTo(CouponCode::class);
    }
}

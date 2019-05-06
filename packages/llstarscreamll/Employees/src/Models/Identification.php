<?php

namespace llstarscreamll\Employees\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use llstarscreamll\Employees\Models\Employee;

/**
 * Class Identification.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Identification extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id', 'name', 'code',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Related employee.
     *
     * @return mixed
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

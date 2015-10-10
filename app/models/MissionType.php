<?php
namespace App\Models;
class MissionType extends Model {
    protected $table = 'mission_types';
    protected $primaryKey = 'mission_type_id';
    public $timestamps = false;

    protected $hidden = [];
    protected $appends = [];
    protected $fillable = [];
    protected $guarded = [];

    // Relationships
    public function missions() {
        return $this->hasMany('Mission');
    }
}
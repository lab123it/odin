<?php
namespace Lab123\Odin\Entities;

use Illuminate\Database\Eloquent\Model;
use Request;
use App;

abstract class Entity extends Model
{

    /**
     * The params attributes.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * The resource name from model.
     *
     * @var string
     */
    protected $resource = '';

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes            
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        /* Adiciona o Hash Id nas entidades */
        if (config('odin.hashid')) {
            self::addPublicId();
        }
    }

    /**
     * Adiciona propriedade public_id na serialização do recurso
     *
     * @return void
     */
    public function addPublicId()
    {
        parent::append([
            'public_id'
        ]);
    }

    /**
     * Return resource
     *
     * @return array
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Return resource URL
     *
     * @return array
     */
    public function getResourceURL()
    {
        return url() . '/' . $this->getResource() . '/' . $this->getId();
    }

    /**
     * Create link attribute to client
     *
     * @return array
     */
    public function getResourceData()
    {
        return [
            'resource' => $this->getResource(),
            'uri' => url() . '/' . $this->getResource() . '/' . $this->getId()
        ];
    }

    /**
     * Retorna o Id do resource
     *
     * @return array
     */
    public function getId()
    {
        if (config('odin.hashid')) {
            return $this->getPublicIdAttribute();
        }
        return $this->id;
    }

    /**
     * Retorna o hash id a partir do Id
     *
     * @return void
     */
    protected function getPublicIdAttribute()
    {
        $hashids = App::make('Hashids');
        return $hashids->encode($this->attributes['id']);
    }

    /**
     * Cria o hash id a partir do Id
     *
     * @return void
     */
    protected function setPublicIdAttribute($value)
    {
        $hashids = App::make('Hashids');
        $this->attributes['public_id'] = $hashids->decode($this->attributes['id']);
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @return array
     */
    
    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $array = parent::attributesToArray();
        
        return $this->transform($array);
    }

    /**
     * Transforma a entidade
     *
     * @return array
     */
    public function transform(array $array)
    {
        $transformation = $this->getTransformation();
        $transformed = [];
        
        foreach ($transformation as $name => $new_name) {
            if (! key_exists($name, $array)) {
                continue;
            }
            
            $transformed[$new_name] = $array[$name];
        }
        
        if (key_exists('public_id', $array)) {
            $transformed['id'] = $array['public_id'];
        }
        
        $transformed['resource'] = $this->getResourceData();
        
        return $transformed;
    }

    /**
     * Get the rules attributes for the model.
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [];
        foreach ($this->fields as $field => $extra) {
            
            if (is_int($field)) {
                continue;
            }
            $rule = (key_exists('rules', $extra)) ? $extra['rules'] : '';
            
            $rules[$field] = $rule;
        }
        
        return $rules;
    }

    /**
     * Get the transformation attributes for the model.
     *
     * @return array
     */
    public function getTransformation()
    {
        $transforms = [];
        foreach ($this->fields as $field => $extra) {
            if (is_int($field)) {
                $transforms[$extra] = $extra;
                continue;
            }
            
            $transform = (key_exists('transform', $extra)) ? $extra['transform'] : $field;
            
            if ($transform === false) {
                continue;
            }
            
            $transforms[$field] = $transform;
        }
        
        return $transforms;
    }
}
<?
class PostRecordBase 
{
  static $type_info = array();
  static $loaded = array();
  static $in_save=0;
  
  static function type()
  {
    return W::singularize(W::tableize(get_called_class()));
  }
  
  static function find($data=array())
  {
    if(is_numeric($data)) // Assume it is a post ID
    {
      $data = array(
        'p'=>$data,
      );
    }
    $query = array(
      'suppress_filters'=>false,
      'post_type'=>static::type(),
      'post_status'=>'any',
      'numberposts'=>1,
    );
    $query = array_merge($data, $query);
    if(isset($query['meta_query']))
    {
      $query['meta_query'] = self::fix_data($query['meta_query']);
    }
    
    $objs = get_posts($query);
    if(count($objs)==0) return null;
    $c = get_called_class();
    return new $c($objs[0]);
  }
  
  static function fix_data($data)
  {
    if(is_object($data)) return $data;
    $ret = array();
    foreach($data as $k=>$v)
    {
      if(is_numeric($k))
      {
        $ret[] = $v;
        continue;
      }
      $ret[] = array('key'=>$k, 'value'=>$v, 'compare'=>'=');
    }
    return $ret;
  }

  static function find_all($data=array())
  {
    $defaults = array(
      'post_type'=>static::type(),
      'post_status'=>'any',
      'numberposts'=>0,
      'suppress_filters'=>false,
    );
    $query = array_merge($data, $defaults);
    if(isset($query['meta_query']))
    {
      $query['meta_query'] = self::fix_data($query['meta_query']);
    }
    $objects = get_posts($query);
    $ret = array();
    $c = get_called_class();
    foreach($objects as $l)
    {
      $nl = new $c($l);
      $ret[] = $nl;
    }
    return $ret;
  }
  
  function permalink()
  {
    return get_permalink($this->ID);
  }
  
  static function init()
  {
    $record_type = W::singularize(W::tableize(get_called_class()));
    if(isset(self::$type_info[$record_type])) return;

    $class = get_called_class();
    $all_fields = array();
    while($class != __CLASS__)
    {
      // Register validations
      if(method_exists($class, 'fields'))
      {
        $func_name = "$class::fields";
        $fields = call_user_func($func_name);
        if(count(array_intersect_key($all_fields, $fields))>0)
        {
          trigger_error("Field names in $class conflict with parent fields.", array_intersect_key($all_fields, $fields));
        }
        $all_fields = array_merge($all_fields, $fields);
        self::fixup($all_fields);
        
        foreach($all_fields as $field_name=>$info)
        {
          $r = $info['required'];
          if(!$r) continue;
          if(!is_callable($r))
          {
            $r = function($obj) use ($field_name) {
              if(!$obj->$field_name)
              {
                $obj->errors[] = new RecordError("is required", $field_name);
              }
            };
          }
          add_action("{$record_type}_validate", $r);
        }
      }
      
      // Register actions
      if(method_exists($class, 'actions'))
      {
        $func_name = "$class::actions";
        $actions = call_user_func($func_name);
        foreach($actions as $k=>$v)
        {
          $action_name = "wp_models_{$record_type}_$k";
          add_action($action_name, $v);
        }
      }
      $class = get_parent_class($class);
    }
    self::fixup($all_fields);
    self::$type_info[$record_type] = array(
      'fields'=>$all_fields,
    );
  }
  
  
  static function info()
  {
    $record_type = W::singularize(W::tableize(get_called_class()));
    if(!isset(self::$type_info[$record_type])) return array();
    return self::$type_info[$record_type];
  }
  
  
  static function get($obj_or_id)
  {
    $id = $obj_or_id;
    if(is_object($obj_or_id))
    {
      $id = $obj_or_id->ID;
    }
    $type = get_post_type($id);
    if(!isset(self::$loaded[$id]))
    {
      $class = W::classify($type);
      $obj = $class::find($id);
      self::$loaded[$id] = $obj;
    }
    return self::$loaded[$id];
  }
  
  function copy()
  {
    $data = array();
    foreach($this->_fields as $k=>$field_info)
    {
      if(!$field_info['should_copy']) continue;
      $mapped_name = $field_info['name'];
      $data[$mapped_name] = $this->$mapped_name;
    }
    $class = get_called_class();
    return new $class($data);
  }
  
  function deserialize_date($obj, $v) 
  {
    $dt = date_create_from_format('Y-m-d H:i:s', $v);
    return $dt->format('U');
  }
  
  function __construct($data = array())
  {
    $record_type = W::singularize(W::tableize(get_called_class()));
    self::init();
    
    $this->_object_type = $record_type;
    $this->_core_fields = array(
      'ID'=>array('default'=>null, 'should_copy'=>false,),
      'comment_status'=>array('default'=>'closed', 'should_copy'=>false,),
      'ping_status'=>array('default'=>'closed', 'should_copy'=>false,),
      'post_author'=>array('default'=>get_current_user_id(), 'should_copy'=>true,),
      'post_type'=>array('default'=>$this->_object_type, 'should_copy'=>true,),
      'post_title'=>array('default'=>null, 'should_copy'=>true,),
      'post_content'=>array('default'=>null, 'should_copy'=>true,),
      'post_status'=>array('default'=>'publish', 'should_copy'=>false,),
      'post_date_gmt'=>array('default'=>null, 'should_copy'=>false, 'deserialize'=>array($this, 'deserialize_date')),
      'post_modified_gmt'=>array('default'=>null, 'should_copy'=>false, 'deserialize'=>array($this, 'deserialize_date')),
    );
    $this->_core_meta_fields = array(
      '_record_version'=>array('default'=>1),
    );
    $this->fixup($this->_core_fields);
    $this->fixup($this->_core_meta_fields);
    $this->_meta_fields = array_merge(self::$type_info[$record_type]['fields'], $this->_core_meta_fields) ;
    $this->fixup($this->_meta_fields);
    $this->_fields = array_merge($this->_meta_fields, $this->_core_fields);
    
    $this->errors = array();
    $this->is_valid = true;

    do_action("wp_models_{$this->_object_type}_before_load", $this);

    $this->load($data);

    $data = (object)$data;
    // Initialize defaults and store original values
    $this->_originals = array();
    foreach($this->_fields as $k=>$info)
    {
      $mapped_name = $info['name'];
      $v = $info['default'];
      if(!isset($this->$mapped_name))
      {
        $this->$mapped_name = $v;
        if(isset($data->$mapped_name))
        {
          $this->$mapped_name = $data->$mapped_name;  
        }
      }
      $this->_originals[$mapped_name] = $this->$mapped_name;
    }
    
    $this->migrate();
    do_action("wp_models_{$this->_object_type}_after_load", $this);
  }
  
  static function fixup(&$fields)
  {
    
    foreach($fields as $k=>$v)
    {
      if(is_string($v)) // Assume name if string
      {
        unset($fields[$k]);
        $k = $v;
        $v = array('name'=>$v);
      }
      $defaults = array(
        'name'=>$k,
        'default'=>null,
        'should_copy'=>true,
        'required'=>false,
      );
      $fields[$k] = array_merge($defaults, $v);
    }
  }
  
  function is_dirty($field_name)
  {
    return $this->$field_name == $this->_originals[$field_name];
  }
  
  static function migrations()
  {
    return array();
  }
  
  function migrate()
  {
    $migrations = static::migrations();
    if(count($migrations)==0) return;
    array_unshift($migrations, function($obj){}); // Migration 0 is always empty
    if($this->_record_version == count($migrations)) return;
    for($i=$this->_record_version;$i<count($migrations);$i++)
    {
      $migrations[$i]($this);
    }
    $this->_record_version = count($migrations);
  }
  
  function load($data)
  {
    if(is_object($data))
    {
      $this->load_object($data);
    } else {
      $this->load_struct($data);
    }
  }
  
  function load_struct($data)
  {
    $this->ID = null;
    if(is_numeric($data))
    {
      $data = array('ID'=>$data);
    }
    if(isset($data['ID']))
    {
      $this->ID = $data['ID'];
    }
    
    // Load the existing post and meta if there is one
    if($this->ID)
    {
      $obj = get_post($this->ID);
      $this->load_object($obj);
    }
  }
  
  function load_object($data)
  {
    foreach($data as $k=>$v)
    {
      $name = $k;
      if(isset($this->_fields[$k]))
      {
        $name = $this->_fields[$k]['name'];
      }
      $this->$name = $v;
      if(isset($this->_fields[$k]['deserialize']))
      {
        $f = $this->_fields[$k]['deserialize'];
        if(is_callable($f))
        {
          $this->$name = $f($this, $v);
        } else {
          $this->$name = call_user_func($f,$this, $v);
        }
      }
    }
    $meta = get_post_meta($this->ID, null);
    if($meta)
    {
      foreach($this->_meta_fields as $k=>$info)
      {
        $mapped_name = $info['name'];
        $this->$mapped_name = $info['default'];
        if(isset($meta[$k]))
        {
          $this->$mapped_name = $meta[$k][0];
        }
      }
    }
  }
  
  function save()
  {
    self::$in_save++;
    if(self::$in_save>10)
    {
      trigger_error("Recursion detected in save {$this->_object_type}");
    }
    if(!$this->validate())
    {
      self::$in_save--;
      trigger_error("Attempt to save invalid model {$this->_object_type}", $this->errors);
    }
    do_action("wp_models_{$this->_object_type}_before_save", $this);
    $post = array();
    foreach($this->_core_fields as $k=>$info)
    {
      $mapped_name = $info['name'];
      $post[$k] = $this->$mapped_name;
    }
    $this->ID = wp_insert_post($post); // insert or update - this function does both
    if(!$this->ID) 
    {
      $this->errors[] = new RecordError("Post failed");
      return false;
    }
    foreach($this->_meta_fields as $field_name=>$field_info)
    {
      $mapped_name = $field_info['name'];
      update_post_meta($this->ID, $mapped_name, $this->$field_name);
    }
    do_action("wp_models_{$this->_object_type}_after_save", $this);
    self::$in_save--;
    return true;
  }
  
  function validate()
  {
    $this->errors = array();
    foreach($this->_fields as $k=>$info)
    {
      $mapped_name = $info['name'];
      $this->$mapped_name = trim($this->$mapped_name);
    }
    do_action("wp_models_{$this->_object_type}_before_validate", $this);
    $this->is_valid = count($this->errors)==0;
    do_action("wp_models_{$this->_object_type}_validate", $this);
    $this->is_valid = count($this->errors)==0;
    do_action("wp_models_{$this->_object_type}_after_validate", $this);
    $this->is_valid = count($this->errors)==0;
    return $this->is_valid;
  }
  
  function to_array()
  {
    $fields_include = func_get_args();
    $data = array();
    if(!$fields_include) $fields_include = array_keys($this->_fields);
    foreach($fields_include as $field_name)
    {
      $data[$field_name] = $this->$field_name;
    }
    return apply_filters("{$this->_object_type}_to_array", $data);
  }

  function to_json($fields_include = array())
  {
    return json_encode(apply_filters("{$this->_object_type}_to_json", $this->to_array($fields_include)));
  }
  
  function __get($name)
  {
    if(!method_exists($this,$name))
    {
      trigger_error("Unknown getter: {$name}.");
    }
    return $this->$name();
  }
  
  static function __callstatic($name, $args)
  {
    if(preg_match("/find_by_(.+)/", $name, $matches))
    {
      switch($matches[1])
      {
        case 'ID':
          $query['p'] = $args[0];
          break;
        default:
          $query['meta_query'] = array(
            array('key'=>$name, 'compare'=>'=', 'value'=>$args[0]),
          );
      }
      $class = get_called_class();
      return call_user_func("$class::find", $query);
    }
  }
  
}

<?
class PostRecordBase 
{
  static $type_info = array();
  static $loaded = array();
  static $in_save=0;
  static $params = array();
  
  static function type()
  {
    return W::singularize(W::tableize(get_called_class()));
  }
  
  static function find($params=array())
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
    $query = array_merge($query, $data);
    if(isset($query['meta_query']))
    {
      $query['meta_query'] = self::fix_data($query['meta_query']);
    }
    
    $objs = get_posts($query);
    if(count($objs)==0) return null;
    $c = get_called_class();
    return new $c($objs[0]);
  }
  
  static function find_all($params=array())
  {
    $params = self::fix_params($params);
    
    self::$params = $params;
    
    add_filter( 'posts_where', array('PostRecordBase', 'filter_where'));
    add_filter( 'posts_join', array('PostRecordBase', 'filter_join'));
    add_filter( 'posts_orderby', array('PostRecordBase', 'filter_orderby'));
    $objects = get_posts($params['query']);
    remove_filter( 'posts_where', array('PostRecordBase', 'filter_where'));
    remove_filter( 'posts_join', array('PostRecordBase', 'filter_join'));
    remove_filter( 'posts_orderby', array('PostRecordBase', 'filter_orderby'));
    
    $ret = array();
    $c = get_called_class();
    foreach($objects as $l)
    {
      $nl = new $c($l);
      $ret[] = $nl;
    }
    return $ret;
  }
  
  static function fix_params($params)
  {
    global $wpdb;

    $defaults = array(
      'query'=>array(),
      'conditions'=>array(),
      'order'=>'',
      'joins'=>array(),
    );
    $params = array_merge($defaults, $params);

    $type_info = self::info();
    $meta_count = 1;
    foreach($type_info['fields'] as $mapped_name=>$info)
    {
      $search = sprintf('/(^|\W)(`?%s`?)($|\W)/', preg_quote($mapped_name));
      $join_name = "t_{$mapped_name}";
      $params['conditions'][0] = preg_replace($search, "\\1`{$join_name}`.`meta_value`\\3", $params['conditions'][0], -1, $where_count);
      $params['order'] = preg_replace($search, "\\1`{$join_name}`.`meta_value`\\3", $params['order'], -1, $order_count);
      if($where_count>0 || $order_count>0)
      {
        $params['joins'][] = "left outer join `{$wpdb->postmeta}` `{$join_name}` on `{$join_name}`.`post_id` = `{$wpdb->posts}`.`ID` and `{$join_name}`.`meta_key` = '{$info['real_name']}'";
      }
    }
    $params['conditions'] = W::db_interpolate($params['conditions']);
    
    $defaults = array(
      'post_type'=>static::type(),
      'post_status'=>'publish',
      'suppress_filters'=>false,
      'nopaging'=>true,
    );
    $params['query'] = array_merge($defaults, $params['query']);

    if(isset($params['query']['meta_query']))
    {
      $params['query']['meta_query'] = self::fix_data($params['query']['meta_query']);
    }

    return $params;    
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
  
  
  function filter_orderby($orderby)
  {
    $orderby = self::$params['order'];
    return $orderby;
  }

  function filter_where($where)
  {
    $where .= ' and ' . self::$params['conditions'];
    return $where;
  }

  function filter_join($join)
  {
    $join .= ' '. join(' ', self::$params['joins']);
    return $join;
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
    foreach($this->_fields as $mapped_name=>$field_info)
    {
      if(!$field_info['should_copy']) continue;
      $data[$mapped_name] = $this->$mapped_name;
    }
    $class = get_called_class();
    return new $class($data);
  }
  
  function deserialize_date($obj, $v) 
  {
    $dt = date_create_from_format('Y-m-d H:i:s', $v);
    if(!$dt) dprint($dt,true);
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

    $this->_originals = array();
    $this->load($data);

    $data = (object)$data;

    $this->migrate();
    do_action("wp_models_{$this->_object_type}_after_load", $this);
  }
  
  static function fixup(&$fields)
  {
    
    foreach($fields as $mapped_name=>$v)
    {
      if(is_string($v)) // Assume name if string
      {
        unset($fields[$mapped_name]);
        $mapped_name = $v;
        $v = array('real_name'=>$mapped_name);
      }
      $defaults = array(
        'real_name'=>$mapped_name,
        'default'=>null,
        'should_copy'=>true,
        'required'=>false,
      );
      $fields[$mapped_name] = array_merge($defaults, $v);
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
  
  function find_mapped_name($real_name)
  {
    foreach($this->_fields as $mapped_name=>$info)
    {
      if($info['real_name']==$real_name) return $mapped_name;
    }
    return null;
  }
  
  function load_object($data)
  {
    foreach($data as $real_name=>$v)
    {
      $this->init_field($real_name, $v);
    }
  }
  
  function init_mapped_field($mapped_name, $raw_value=null)
  {
    if($raw_value===null) $raw_value = $this->_fields[$mapped_name]['default'];
    $raw_value=trim($raw_value);
    $this->$mapped_name = $raw_value;
    if(isset($this->_fields[$mapped_name]['deserialize']))
    {
      $f = $this->_fields[$mapped_name]['deserialize'];
      if(is_callable($f))
      {
        $this->$mapped_name = $f($this, $this->$mapped_name);
      } else {
        $this->$mapped_name = call_user_func($f,$this, $this->$mapped_name);
      }
    }
    $this->_originals[$mapped_name] = $this->$mapped_name;
    
    return $this->$mapped_name;
  }
  
  function init_field($real_name, $raw_value)
  {
    $mapped_name = $this->find_mapped_name($real_name);
    if(!$mapped_name) return null;
    return $this->init_mapped_field($mapped_name, $raw_value);
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
    foreach($this->_core_fields as $mapped_name=>$info)
    {
      $real_name = $info['real_name'];
      $post[$real_name] = $this->$mapped_name;
    }
    $this->ID = wp_insert_post($post); // insert or update - this function does both
    if(!$this->ID) 
    {
      $this->errors[] = new RecordError("Post failed");
      return false;
    }
    foreach($this->_meta_fields as $mapped_name=>$field_info)
    {
      if(!isset($this->_originals[$mapped_name])) continue; // not loaded
      $real_name = $field_info['real_name'];
      if($this->$mapped_name == $this->_originals[$mapped_name]) continue; // not dirty
      update_post_meta($this->ID, $real_name, $this->$mapped_name);
    }
    do_action("wp_models_{$this->_object_type}_after_save", $this);
    self::$in_save--;
    return true;
  }
  
  function validate()
  {
    $this->errors = array();
    foreach($this->_fields as $mapped_name=>$info)
    {
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
    if($this->ID)
    {
      foreach($this->_meta_fields as $mapped_name=>$info)
      {
        if($mapped_name==$name)
        {
          $v = get_post_meta($this->ID, $info['real_name'], true);
          return $this->init_mapped_field($mapped_name, $v);
        }
      }
    }
        
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

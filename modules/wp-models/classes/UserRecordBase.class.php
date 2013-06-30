<?
class UserRecordBase 
{
  static $type_info = array();
  static $loaded = array();
  
  static function type()
  {
    return W::singularize(W::tableize(get_called_class()));
  }
  
  static function find($query=array())
  {
    $objs = get_users($query);
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
    $data = self::fix_data($data);
    $query = array(
      'meta_query'=>$data,
    );
    $objects = get_users($query);
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
        if(count(array_intersect($all_fields, $fields))>0)
        {
          W::error("Field names in $class conflict with parent fields.", array_intersect($all_fields, $fields));
        }
        $all_fields = array_merge($all_fields, $fields);
        foreach($fields as $field_name=>$info)
        {
          if(!$info['required']) continue;
          add_action("{$record_type}_validate", function($obj) use ($field_name) {
            if(!$obj->$field_name)
            {
              $obj->errors[] = new RecordError("is required", $field_name);
            }
          });
        }
      }
      
      // Register actions
      if(method_exists($class, 'actions'))
      {
        $func_name = "$class::actions";
        $actions = call_user_func($func_name);
        foreach($actions as $k=>$v)
        {
          $action_name = "{$record_type}_$k";
          add_action($action_name, $v);
        }
      }
      $class = get_parent_class($class);
    }
    self::fixup($all_fields);
    self::$type_info[$record_type] = array(
      'fields'=>$all_fields,
    );
    
    $class = get_called_class();
    add_action('edit_user_profile', "$class::add_profile_fields");
    
    add_action( 'personal_options_update', "$class::save_profile_fields" );
    add_action( 'edit_user_profile_update', "$class::save_profile_fields" );
  }
  
  static function save_profile_fields($obj_id)
  {
    $t = static::type();
    if(!isset($_REQUEST[$t])) return;
    if ( !current_user_can( 'edit_user', $obj_id ) ) return;
    if ( !wp_verify_nonce( $_REQUEST["{$t}_edit_nonce"], "{$t}_edit") ) return;

    $class = get_called_class();
    $fields = call_user_func(array($class, 'fields'));
    self::fixup($fields);
    foreach($fields as $k=>$info)
    {
      $mapped_name = $info['name'];
      if(!isset($_REQUEST[$t][$mapped_name])) continue;
      update_user_meta($obj_id, $k, $_REQUEST[$t][$mapped_name]);
    }
  }
  
  static function add_profile_fields($user)
  {
    $rec = User::get($user);
    $t = static::type();
    $class = static::type();
    echo "<h3>".W::humanize($class)."</h3>";
    echo "<table class='form-table'>";
    wp_nonce_field("{$t}_edit","{$t}_edit_nonce" );
    $info = static::info();
    foreach($info['fields'] as $field_name => $field_info)
    {
    	$name = "{$t}[$field_name]";
      ?>
        <tr>
          <th><label for="<?=$name?>"><?=W::humanize($field_name)?></th>
          <td><input id="<?=$field_name?>" size="37" name="<?=$name?>" value="<?=W::p($name, $rec->$field_name)?>" /></td>
        </tr>
      <?
    }
    echo "</table>";
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
    
    if(!isset(self::$loaded[$id]))
    {
      $obj = User::find(array('include'=>array($id)));
      self::$loaded[$id] = $obj;
    }
    return self::$loaded[$id];
  }
  
  function __construct($data = array())
  {
    $record_type = W::singularize(W::tableize(get_called_class()));
    self::init();
    
    $this->_object_type = $record_type;
    $this->_core_fields = array(
      'ID'=>array('default'=>null,),
      'user_login'=>array('name'=>'login'),
      'user_displayname'=>array('name'=>'screen_name'),
      'user_email'=>array('name'=>'email'),
    );
    $this->_core_meta_fields = array(
      '_record_version'=>array('default'=>1),
    );
    $this->fixup($this->_core_fields);
    $this->fixup($this->_core_meta_fields);
    $this->_user_fields = self::$type_info[$record_type]['fields'] ;
    $this->fixup($this->_user_fields);
    $this->_fields = array_merge(array_merge($this->_user_fields, $this->_core_meta_fields), $this->_core_fields);
    
    $this->errors = array();
    $this->is_valid = true;

    do_action("{$this->_object_type}_before_load", $this);

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
      }
      $this->_originals[$mapped_name] = $this->$mapped_name;
      if(isset($data->$mapped_name))
      {
        $this->$mapped_name = $data->$mapped_name;  
      }
    }
    $this->migrate();
    do_action("{$this->_object_type}_after_load", $this);
  }
  
  function fixup(&$fields)
  {
    foreach($fields as $k=>$v)
    {
      if(!isset($fields[$k]['default'])) $fields[$k]['default'] = null;
      if(!isset($fields[$k]['name'])) $fields[$k]['name'] = $k;
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

  static function relations()
  {
    return array();
  }
  
  function migrate()
  {
    $migrations = static::migrations();
    if(count($migrations)==0) return;
    array_unshift($migrations, null);
    if($this->_record_version == count($migrations)) return;
    for($i=$this->_record_version;$i<count($migrations);$i++)
    {
      $migrations[$i]($this);
    }
    $this->_record_version = count($migrations);
    $this->save();
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
    
    // Load the existing object and meta if there is one
    if($this->ID)
    {
      $obj = get_user($this->ID);
      $this->load_object($obj);
    }
  }
  
  function load_object($data)
  {
    foreach($data->data as $k=>$v)
    {
      $name = $k;
      if(isset($this->_fields[$k]))
      {
        $name = $this->_fields[$k]['name'];
      }
      $this->$name = $v;
    }
    $meta = get_user_meta($this->ID, null);
    if($meta)
    {
      foreach($this->_user_fields as $k=>$info)
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
    if(!$this->validate())
    {
      W::error("Attempt to save invalid model {$this->_object_type}", $this->errors);
    }
    do_action("{$this->_object_type}_before_save", $this);
    $data = array();
    foreach($this->_core_fields as $k=>$info)
    {
      $mapped_name = $info['name'];
      $data[$k] = $this->$mapped_name;
    }
    $this->ID = wp_update_user($data); // insert or update - this function does both
    if(!is_numeric($this->ID)) 
    {
      $this->errors[] = new RecordError("Save failed");
      return false;
    }
    foreach($this->_user_fields as $field_name=>$field_info)
    {
      $mapped_name = $field_info['name'];
      $res = update_user_meta($this->ID, $mapped_name, $this->$field_name);
    }
    do_action("{$this->_object_type}_after_save", $this);
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
    do_action("{$this->_object_type}_before_validate", $this);
    $this->is_valid = count($this->errors)==0;
    do_action("{$this->_object_type}_validate", $this);
    $this->is_valid = count($this->errors)==0;
    do_action("{$this->_object_type}_after_validate", $this);
    $this->is_valid = count($this->errors)==0;
    return $this->is_valid;
  }
  
  function to_json()
  {
    $data = array(
      'errors'=>$this->errors,
      'fields'=>array(),
    );
    foreach($this->_fields as $k=>$info)
    {
      $mapped_name = $info['name'];
      $data['fields'][$mapped_name] = $this->$mapped_name;
    }
    return json_encode(apply_filters("{$this->_object_type}_to_json", $data));
  }
  
  function __get($name)
  {
    if(method_exists($this,$name))
    {
      return $this->$name();
    }
    $relations = static::relations();
    foreach($relations as $field=>$info)
    {
      if($field!=$name) continue;
      switch($info['type'])
      {
        case 'has_many':
          $this->$name = call_user_func("{$info['class']}::find_all", array($info['key']=>$this->ID));
          return $this->$name;
        default:
          W::error("Unknown relation type: {$info['type']}");
      }
    }
    W::error("Unknown getter: {$name}.");
    
  }
  
}

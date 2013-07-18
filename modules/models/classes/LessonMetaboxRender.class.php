    $class = get_called_class();
    add_action( 'init', function() use($record_type, $class) {
    	register_post_type( $record_type,
    		array(
    			'labels' => array(
    				'name' => __( W::pluralize(W::humanize($record_type)) ),
    				'singular_name' => __( W::humanize($record_type) )
    			),
    		'public' => true,
    		'has_archive' => false,
    		'rewrite'=>W::pluralize($record_type),
    		'register_meta_box_cb'=>"$class::add_metaboxes",
    		'supports'=>array('title', 'editor', 'comments')
    		)
    	);
    });
    
    add_action( 'save_post', function($obj_id) {
      $t = get_post_type($obj_id);
      if(!isset($_REQUEST[$t])) return;
      if ( !current_user_can( 'edit_post', $obj_id ) ) return;
      if ( !wp_verify_nonce( $_REQUEST["{$t}_edit_nonce"], "{$t}_edit") ) return;

      $class = W::classify($t);
      $fields = call_user_func(array($class, 'fields'));
      PostRecordBase::fixup($fields);
      foreach($fields as $k=>$info)
      {
        $mapped_name = $info['name'];
        if(!isset($_REQUEST[$t][$mapped_name])) continue;
        update_post_meta($obj_id, $k, $_REQUEST[$t][$mapped_name]);
      }
    });
    
  
  static function add_metaboxes()
  {
    $class = get_called_class();
    $record_type = W::singularize(W::tableize(get_called_class()));
    add_meta_box( "{$record_type}_metabox", __( W::humanize($record_type) ), "$class::render_metabox", $record_type, 'normal', 'high');
  }
  
  static function render_metabox()
  {
    global $post;
    $rec = self::get($post);
    $t = static::type();
    
    wp_nonce_field("{$t}_edit","{$t}_edit_nonce" );
    $info = static::info();
    foreach($info['fields'] as $field_name => $field_info)
    {
    	$name = "{$t}[$field_name]";
      ?>
    	<p><label for="<?=$name?>"><?=W::humanize($field_name)?><br />
    		<input id="<?=$field_name?>" size="37" name="<?=$name?>" value="<?=W::p($name, $rec->$field_name)?>" /></label></p>
      <?
    }
  }

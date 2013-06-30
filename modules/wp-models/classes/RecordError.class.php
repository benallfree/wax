<?

class RecordError
{
  function __construct($message, $fields = null)
  {
    $this->message = $message;
    if(!is_array($fields)) $fields = array($fields);
    $this->fields = $fields;
  }
}



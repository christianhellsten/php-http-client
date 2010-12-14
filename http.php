<?php
class Response  {
  public function Response($code, $body, $headers, $info=null) {
    #$this->code = $info['http_code'];
    $this->code = $code;
    $this->body = $body;
    $this->headers = $headers;
    $this->info = $info;
    $this->url = $info['url'];
  }
}

class HTTP {
  /**
   * HTTP GET
   *
   * @param string $url
   * @param array $headers: HTTP headers
   * @param array $options: CURL options
   */
  static function get($url, $headers=null, $options=null) {
    return self::execute($url, null, $headers, $options);
  }

  /**
   * HTTP POST
   *
   * @param string $url
   * @param array $post_data: POST data
   * @param array $headers: HTTP headers
   * @param array $options: CURL options
   */
  static function post($url, $post_data, $headers=null, $options=null) {
    if($post_data == null) {
      $post_data = array();
    }
    return self::execute($url, $post_data, $headers, $options);
  }
 
  /**
   * HTTP GET or POST
   */
  protected static function execute($url, $post_data, $headers, $options) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
   
    # POST
    if($post_data) {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    # Set headers
    if($headers) {
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
   
    # Set CURL options
    if(!empty($options)) {
      self::set_options($ch, $options);
    }

    # Execute request
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $curl_error = curl_errno($ch);
   
    # Check for CURL errors
    if(!$curl_error) {
      # Extract HTTP status and body.
      # Note: ignore eg. "HTTP/1.0 200 Connection established" from proxies
      $many = preg_match("#HTTP\/1\.[0,1] (100 Continue|200 Connection established)#", $text);
      if($many) {
        list($header_text, $proxy_status, $body) = explode("\r\n\r\n", $response, 3);
      } else {
        list($header_text, $body) = explode("\r\n\r\n", $response, 2);
      }
      $header_lines = explode("\r\n", $header_text);
 
      # Extract HTTP status
      $header_line = array_shift($header_lines);
      if (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@', $header_line, $matches)) {
         $status = $matches[1];
      }
      # Extract HTTP headers
      $headers = array();
      foreach ($header_lines as $header_line) {
         list($header, $value) = explode(': ', $header_line, 2);
         $headers[$header] = $value;
      }
    } else {
      $error = printf("CURL %s error code: %s\nMessage: %s\nOptions: %s",
                      $url, curl_errno($ch), curl_error($ch), print_r($options, true));
      curl_close($ch); 
      throw new Exception($error);
    } 
    curl_close($ch); 
 
    return new Response($status, $body, $headers, $info);
  }

  /**
   * Set CURL options.
   *
   * @param curl handle $ch
   * @param array $options
   */
  protected static function set_options($ch, $options) {
    # Debug log (file path)
    if(isset($options['debug_log'])) {
      $debug_log = fopen($options['debug_log'], 'a');
      curl_setopt($ch, CURLOPT_STDERR, $debug_log);
      curl_setopt($ch, CURLOPT_VERBOSE, 1);
    }   
    # Follow redirects (boolean)
    if(isset($options['follow_redirects'])) {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $options['follow_redirects']);
    }
    # Cookie jar (file path)
    if(isset($options['cookie_jar'])) {
      curl_setopt($ch, CURLOPT_COOKIEJAR, $options['cookie_jar']);
      curl_setopt($ch, CURLOPT_COOKIEFILE, $options['cookie_jar']);
    }
    # Connect timeout (int)
    if(isset($options['connect_timeout'])) {
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $options['connect_timeout']);
    }
    # SSL verification (boolean)
    if(isset($options['verify_ssl_certificate'])) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['verify_ssl_certificate']);
    }
    # Proxy (string)
    if(isset($options['proxy'])) {
      curl_setopt($ch, CURLOPT_PROXY, $options['proxy']);
    }
  }
}
?>

class Response  {
  public function Response($code, $body, $headers, $info=null) {
    $this->code = $code;
    $this->body = $body;
    $this->headers = $headers;
    $this->info = $info;
  }
}

class HTTP {
  static function get($url, $headers=null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if($headers) {
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    // Execute request
    $response = curl_exec($ch);

    // Split headers and body
    list($header_text, $body) = explode("\r\n\r\n", $response, 2);
    $header_lines = explode("\r\n", $header_text);

    // Extract HTTP status
    $header_line = array_shift($header_lines);
    if (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@', $header_line, $matches)) {
       $status = $matches[1];
    }

    // Extract HTTP headers
    $headers = array();
    foreach ($header_lines as $header_line) {
       list($header, $value) = explode(': ', $header_line, 2);
       $headers[$header] = $value;
    }

    $info = curl_getinfo($ch); 

    curl_close($ch);    

    $status = $info['http_code'];

    return new Response($status, $body, $headers, $info);
  }
}


<?php
	#
	# lib_filter.txt
	#
	# A PHP HTML filtering library
	# Release 6 (10th May 2004)
	#
	# http://iamcal.com/publish/articles/php/processing_html/
	# http://iamcal.com/publish/articles/php/processing_html_part_2/
	#
	# (C)2001-2004 Cal Henderson <cal@iamcal.com>
	#
	#


	$filter = new lib_filter();

	class lib_filter {

		var $tag_counts = array();

		#
		# tags and attributes that are allowed
		#

		var $allowed = array(
			'a' => array('href', 'target'),
			'b' => array(),
#			'img' => array('src', 'width', 'height', 'alt'),
		);


		#
		# tags which should always be self-closing (e.g. "<img />")
		#

		var $no_close = array(
#			'img',
		);


		#
		# tags which must always have seperate opening and closing tags (e.g. "<b></b>")
		#

		var $always_close = array(
			'a',
			'b', 'i', 'em', 'strong'
		);


		#
		# attributes which should be checked for valid protocols
		#

		var $protocol_attributes = array(
			'src',
			'href',
		);


		#
		# protocols which are allowed
		#

		var $allowed_protocols = array(
			'http',
			'ftp',
			'mailto',
		);


		#
		# tags which should be removed if they contain no content (e.g. "<b></b>" or "<b />")
		#

		var $remove_blanks = array(
			'a',
			'b',
		);

		###############################################################

		function go($data){

			$this->tag_counts = array();

			$data = $this->balance_html($data);
			$data = $this->check_tags($data);
			$data = $this->process_remove_blanks($data);

			return $data;
		}

		###############################################################

		function balance_html($data){

			$data = preg_replace("/<([^>]*?)(?=<|$)/", "<$1>", $data);
			$data = preg_replace("/(^|>)([^<]*?)(?=>)/", "$1<$2", $data);

			return $data;
		}

		###############################################################

		function check_tags($data){

			$data = preg_replace("/<(.*?)>/se", "\$this->process_tag(StripSlashes('\\1'))",	$data);

			foreach(array_keys($this->tag_counts) as $tag){
				for($i=0; $i<$this->tag_counts[$tag]; $i++){
					$data .= "</$tag>";
				}
			}

			return $data;
		}

		###############################################################

		function process_tag($data){

			# ending tags
			if (preg_match("/^\/([a-z0-9]+)/si", $data, $matches)){
				$name = StrToLower($matches[1]);
				if (in_array($name, array_keys($this->allowed))){
					if (!in_array($name, $this->no_close)){
						if (isset($this->tag_counts[$name])) {
							$this->tag_counts[$name]--;
							return '</'.$name.'>';
						}
					}
				}else{
					return '';
				}
			}

			# starting tags
			if (preg_match("/^([a-z0-9]+)(.*?)(\/?)$/si", $data, $matches)){
				$name = StrToLower($matches[1]);
				$body = $matches[2];
				$ending = $matches[3];
				if (in_array($name, array_keys($this->allowed))){
					$params = "";
					preg_match_all("/([a-z0-9]+)=\"(.*?)\"/si", $body, $matches_2, PREG_SET_ORDER);
					preg_match_all("/([a-z0-9]+)=([^\"\s]+)/si", $body, $matches_1, PREG_SET_ORDER);
					$matches = array_merge($matches_1, $matches_2);
					foreach($matches as $match){
						$pname = StrToLower($match[1]);
						if (in_array($pname, $this->allowed[$name])){
							$value = $match[2];
							if (in_array($pname, $this->protocol_attributes)){
								$value = $this->process_param_protocol($value);
							}
							$params .= " $pname=\"$value\"";
						}
					}
					if (in_array($name, $this->no_close)){
						$ending = ' /';
					}
					if (in_array($name, $this->always_close)){
						$ending = '';
					}
					if (!$ending){
						if (isset($this->tag_counts[$name])){
							$this->tag_counts[$name]++;
						}else{
							$this->tag_counts[$name] = 1;
						}
					}
					if ($ending){
						$ending = ' /';
					}
					return '<'.$name.$params.$ending.'>';
				}else{
					return '';
				}
			}

			# garbage, ignore it
			return '';
		}

		###############################################################

		function process_param_protocol($data){

			if (preg_match("/^([^:]+)\:/si", $data, $matches)){
				if (!in_array($matches[1], $this->allowed_protocols)){
					$data = '#'.substr($data, strlen($matches[1])+1);
				}
			}

			return $data;
		}

		###############################################################

		function process_remove_blanks($data){
			foreach($this->remove_blanks as $tag){

				$data = preg_replace("/<{$tag}[^>]*><\\/{$tag}>/", '', $data);
				$data = preg_replace("/<{$tag}[^>]*\\/>/", '', $data);
			}
			return $data;
		}

		###############################################################
	}

?>

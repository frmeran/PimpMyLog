<?php

function apache_get_config( $type , $file , $software , $counter ) {

	/////////////////////////////////////////////////////////
	// Apache error files are not the same on 2.2 and 2.4 //
	/////////////////////////////////////////////////////////
	if ( $type == 'error' ) {

		// Get the first 10 lines and try to guess
		// This is not really
		$firstline = '';
		$handle    = @fopen( $file , 'r' );
		$remain    = 10;
		if ( $handle ) {
			while ( ( $buffer = fgets( $handle , 4096 ) ) !== false ) {
				$test = @preg_match('|^\[(.*) (.*) (.*) (.*):(.*):(.*)\.(.*) (.*)\] \[(.*):(.*)\] \[pid (.*)\] .*\[client (.*):(.*)\] (.*)(, referer: (.*))*$|U', $buffer );
				if ( $test === 1 ) {
					break;
				}
				$remain--;
				if ($remain<=0) {
					break;
				}
			}
			fclose($handle);
		}


		/////////////////////
		// Error 2.4 style //
		/////////////////////
		if ( $test === 1 ) {
			return<<<EOF
\$files[ '$software$counter' ] = array(
	'display' => 'Apache Error #$counter',
	'path'    => '$file',
	'refresh' => 5,
	'max'     => 10,
	'notify'  => true,
	'format'  => array(
		'regex' => '|^\[(.*) (.*) (.*) (.*):(.*):(.*)\.(.*) (.*)\] \[(.*):(.*)\] \[pid (.*)\] .*\[client (.*):(.*)\] (.*)(, referer: (.*))*$|U',
		'match' => array(
			'Date'     => array(
				'M' => 2,
				'D' => 3,
				'H' => 4,
				'I' => 5,
				'S' => 6,
				'Y' => 8,
			),
			'IP'       => 12,
			'Log'      => 14,
			'Severity' => 10,
			'Referer'  => 16,
		),
		'types' => array(
			'Date'     => 'date:H:i:s',
			'IP'       => 'ip:http',
			'Log'      => 'pre',
			'Severity' => 'badge:severity',
			'Referer'  => 'link',
		),
		'exclude' => array(
			'Log' => array( '/PHP Stack trace:/' , '/PHP *[0-9]*\. /' )
		),
	)
);
EOF;

		}

		/////////////////////
		// Error 2.2 style //
		/////////////////////
		else {
			return<<<EOF
\$files[ '$software$counter' ] = array(
	'display' => 'Apache Error #$counter',
	'path'    => '$file',
	'refresh' => 5,
	'max'     => 10,
	'notify'  => true,
	'format'  => array(
		'regex' => '|^\[(.*)\] \[(.*)\] (\[client (.*)\] )*((?!\[client ).*)(, referer: (.*))*$|U',
		'match' => array(
			'Date'     => 1,
			'IP'       => 4,
			'Log'      => 5,
			'Severity' => 2,
			'Referer'  => 7,
		),
		'types' => array(
			'Date'     => 'date:H:i:s',
			'IP'       => 'ip:http',
			'Log'      => 'pre',
			'Severity' => 'badge:severity',
			'Referer'  => 'link',
		),
		'exclude' => array(
			'Log' => array( '/PHP Stack trace:/' , '/PHP *[0-9]*\. /' )
		),
	)
);
EOF;

		}

		return $conf;
	}

	////////////////
	// Access log //
	////////////////
	else if ( $type == 'access' ) {

		return<<<EOF
\$files[ '$software$counter' ] = array(
	'display' => 'Apache Access #$counter',
	'path'    => '$file',
	'refresh' => 0,
	'max'     => 10,
	'notify'  => false,
	'format'  => array(
		'regex' => '|^(.*) (.*) (.*) \[(.*)\] "(.*) (.*) (.*)" ([0-9]*) (.*) "(.*)" "(.*)"( [0-9]*/([0-9]*))*\$|U',
		'match' => array(
			'Date'    => 4,
			'IP'      => 1,
			'CMD'     => 5,
			'URL'     => 6,
			'Code'    => 8,
			'Size'    => 9,
			'Referer' => 10,
			'UA'      => 11,
			'User'    => 3,
			'μs'      => 13,
		),
		'types' => array(
			'Date'    => 'date:H:i:s',
			'IP'      => 'ip:geo',
			'URL'     => 'txt',
			'Code'    => 'badge:http',
			'Size'    => 'numeral:0b',
			'Referer' => 'link',
			'UA'      => 'ua:{os.name} {os.version} | {browser.name} {browser.version}/100',
			'μs'      => 'numeral:0,0',
		),
		'exclude' => array(
			'URL' => array( '/favicon.ico/' , '/\.pml\.php\.*\$/' ),
			'CMD' => array( '/OPTIONS/' )
		),
	)
);
EOF;

	}
}

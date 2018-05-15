<?php

$words = [ ];
$places = [ ];

    // convert from degrees-minutes-seconds to decimal degrees
    // reference: https://geonames.usgs.gov/domestic/faqs.htm
function dms2dec( $str ) {
        // left pad to 8 characters to generalize for latitude or longitude
    $str = str_pad( preg_replace( "/[^0-9A-Z]/", "",
        strtoupper( $str ) ), 8, "0", STR_PAD_LEFT );
    $deg = substr( $str, 0, 3 );
    $min = substr( $str, 3, 2 );
    $sec = substr( $str, 5, 2 );
    $dir = substr( $str, 7, 1 );
    $val = $sec / 60.0;
    $val += $min;
    $val /= 60.0;
    $val += $deg;
    if( $dir == "W" || $dir == "S" ) {
        $val = -$val;
    }
        // accuracy beyond six digits is unnecessary
    return round( $val, 6 );
}

$file = fopen( "philadelphia-parks.csv", "r" );
    // skip first line (field descriptors)
@fgets( $file );
while( $line = fgets( $file ) ) {
    if( $line = trim( $line ) ) {
        $fields = explode( "|", $line );
            // normalize place-name
        $name = preg_replace( "/[^\A-Za-z0-9&\ ]/", "",
            str_replace( [ " and", " &and" ], " &",
                trim( $fields[ 0 ], '"' ) ) );
            // convert coordinates
        $lat = dms2dec( $fields[ 5 ] );
        $lng = dms2dec( $fields[ 6 ] );
        $places[ ] = [
            $name,
            $lat,
            $lng
        ];
    }
}
fclose( $file );

    // perform huffman coding on place-names
$before = 0;
$after = 0;
    // first pass: determine unique words and their frequencies
$freqs = [ ];
foreach( $places as $place ) {
    $name = $place[ 0 ];
    foreach( explode( " ", $name ) as $word ) {
            // add word to index
        if( !array_key_exists( $word, $freqs ) ) {
            $freqs[ $word ] = 0;
        }
        $freqs[ $word ]++;
    }
}
arsort( $freqs );
    // second pass: replace words with hexidecimal index
$words = [ ];
$i = 0;
foreach( array_keys( $freqs ) as $word ) {
    $key = dechex( $i );
    $words[ $key ] = $word;
    $i++;
}
$i = 0;
foreach( $places as $place ) {
    $before += strlen( $place[ 0 ] );
    $name = [ ];
    foreach( explode( " ", $place[ 0 ] ) as $word ) {
        $name[ ] = array_search( $word, $words );
    }
    $name = implode( " ", $name );
    $place[ 0 ] = $name;
    $places[ $i ] = implode( ",", $place );
    $after += strlen( $name );
    $i++;
}
$words = array_values( $words );

    // create javascript file used as data to generate map markers
$file = fopen( "words-places.js", "w" );
fwrite( $file, "var words = " . json_encode( $words ) . ";\n" );
fwrite( $file, "var places = " . json_encode( $places ) . ";\n" );
fclose( $file );

echo "compression ratio: " . round( ( $before / $after ) * 100.0, 2 ) . "%\n";

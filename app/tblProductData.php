<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class tblProductData extends Model
{
    public static function processCsv( $data )
    {	
    	try {
    		DB::table('tblProductData')->insert($data);
    		echo "@app-message: File import complete.";
    		return true;
    	} catch( \Illuminate\Database\QueryException $ex ) {
    		echo "@app-message: Re-run the command after resolving the issue/s below.\n";
		  	echo "@app-message: ".$ex->getMessage(); 
		  	return false;
		}
    }
}

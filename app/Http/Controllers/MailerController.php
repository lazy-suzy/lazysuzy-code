<?php

namespace App\Http\Controllers;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Mailer;
use App\Models\Product;
use Mail;
class MailerController extends Controller 
{
    
    public function send_catalogue() {

        Mail::to('arzan@lazysuzy.com')->send(new Mailer());

        //return MailerProducts::get_mailer_products();
    }

}
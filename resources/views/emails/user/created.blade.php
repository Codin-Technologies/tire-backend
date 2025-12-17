# Welcome to Tire Management System
 
 Hello {{ $user->name }},
 
 Your account has been created by the administrator. Please use the following credentials to login:
 
 **Email:** {{ $user->email }}<br>
 **Temporary Password:** {{ $password }}
 
 Please change your password immediately after logging in.
 
 <x-mail::button :url="config('app.url')">
 Login to System
 </x-mail::button>
 
 Thanks,<br>
 {{ config('app.name') }}
 </x-mail::message>

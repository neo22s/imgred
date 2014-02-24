<?php
class home_Controller extends Controller{
    
    public static function index()
    {
        if (H::isUrl(G('image')))
        {
           $r=Model::factory('rir');
           $r->get(G('image')); 
        }
        else
        {
            $V=new View('home');
            $V->meta_title='rir.li - No Image Hotlinking!';
            $V->content='rir.li - No Image Hotlinking!';         
            $V->render();
        }
       
    }
    
    public static function page($t)
    {
        $V=new View('home');
        $V->meta_title=$t;
        $V->meta_description=$t;
        $V->meta_keywords='Just Another Framework Home';
        $V->content=$t;
        $V->render();
    }
    
}
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Cherry-Pie/Jarboe/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Cherry-Pie/Jarboe/?branch=master)

````
php artisan config:publish --path="/workbench/yaro/jarboe/src/config" yaro/jarboe
php artisan asset:publish --bench="yaro/jarboe"
````

To app/config/app.php
```php
// ...
'Yaro\Jarboe\JarboeServiceProvider',
// ...
'Jarboe'   => 'Yaro\Jarboe\Facades\Jarboe',
'Settings' => 'Yaro\Jarboe\Helpers\Settings',
// ...
```



```php
Route::any('/storage/image', 'TableAdminController@showImageStorage');
//..
public function showImageStorage()
{
    if (Request::isMethod('post')) {
        $options = array(
            'url'      => Request::path(),
            'def_name' => 'settings',
        );
        return Jarboe::table($options);
    }
    return View::make('admin::image_storage');
} // end showImageStorage
```
### Translator
```html
// include translator script
<script src="{{asset('packages/yaro/jarboe/js/translator.js')}}"></script>

// set the current language and translates array into Translator object
<script>
    Translator.lang = '{{App::getLocale()}}';
    Translator.translates = {{json_encode($translates)}};
</script>
```

```php
// get a translator instance and translates
$translator = \Yaro\Jarboe\Helpers\Translate::getInstance();
$translates = $translator->getAllStatic();
```

```javascript
// get localized data like this
Translator.t('Спасибо, ваша заявка принята!')
```

# License
MIT with additional restrictions:

1. SmartAdmin Responsive template must be bought.

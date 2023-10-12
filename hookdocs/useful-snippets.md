## Make ClassifAI taxonomies private

Some users might want to set some of the taxonomies provided by the plugin private, so that their archive pages won't be generated (and thus indexed by search engines).

In order to do that, we can use a filter provided by WordPress Core, namely `register_{$taxonomy}_taxonomy_args` ([see here for the documentation](https://developer.wordpress.org/reference/hooks/register_taxonomy_taxonomy_args/)).

For example, let's say we want to make the `classifai-image-tags` taxonomy private, we would need to add this snippet to either our theme `functions.php` or our custom plugin.

```php
namespace MyPluginOrTheme;

add_filter( 'register_classifai-image-tags_taxonomy_args', __NAMESPACE__ . '\override_taxonomy_args' );

function override_taxonomy_args( $args ) {
	$args['public'] = false;
	return $args;
}
```

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

## Add custom Provider to an existing Feature

Starting in ClassifAI 3.0.0, it is now possible to add your own Provider to an existing Feature. Most of the implementation details are left to you but there are a few key steps that need to be followed:

1. **Create a new Provider class**: This class should extend the base ClassifAI `Provider` class and setup all required methods.

```php
namespace MyPluginOrTheme;

use Classifai\Providers\Provider;

class MyProvider extends Provider {
    /**
     * The Provider ID.
     *
     * Required and should be unique.
     */
    const ID = 'my_provider';

    /**
     * MyProvider constructor.
     *
     * @param \Classifai\Features\Feature $feature_instance The feature instance.
     */
    public function __construct( $feature_instance = null ) {
        $this->feature_instance = $feature_instance;
    }

    /**
     * This method will be called by the feature to render the fields
     * required by the provider, such as API key, endpoint URL, etc.
     *
     * This should also register settings that are required for the feature
     * to work.
     */
    public function render_provider_fields() {
        $settings = $this->feature_instance->get_settings( static::ID );

        $this->add_api_key_field();
    }

    /**
     * Returns the default settings for this provider.
     *
     * @return array
     */
    public function get_default_provider_settings(): array {
        $common_settings = [
            'api_key'       => '',
            'authenticated' => false,
        ];

        return $common_settings;
    }

    /**
     * Sanitize the settings for this provider.
     *
     * Can also be useful to verify the Provider API connection
     * works as expected here, returning an error if needed.
     *
     * @param array $new_settings The settings array.
     * @return array
     */
    public function sanitize_settings( array $new_settings ): array {
        $settings = $this->feature_instance->get_settings();

        // Ensure proper validation of credentials happens here.
        $new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );
        $new_settings[ static::ID ]['authenticated'] = true;

        return $new_settings;
    }

    /**
     * Common entry point for all REST endpoints for this provider.
     *
     * All Features will end up calling the rest_endpoint_callback method for their assigned Provider.
     * This method should validate the route that is being called and then call the appropriate method
     * for that route. This method typically will validate we have all the requried data and if so,
     * make a request to the appropriate API endpoint.
     *
     * @param int    $post_id The Post ID we're processing.
     * @param string $route_to_call The route we are processing.
     * @param array  $args Optional arguments to pass to the route.
     * @return string|WP_Error
     */
    public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'text-domain' ) );
        }

        $route_to_call = strtolower( $route_to_call );
        $return        = '';

        // Handle all of our routes.
        switch ( $route_to_call ) {
            case 'test':
                // Ensure this method exists.
                $return = $this->generate( $post_id, $args );
                break;
        }

        return $return;
    }

    /**
     * Returns the debug information for the provider settings.
     *
     * This is used to display various settings in the Site Health screen.
     * Not required but useful for debugging.
     *
     * @return array
     */
    public function get_debug_information(): array {
        $settings   = $this->feature_instance->get_settings();
        $debug_info = [];

        return $debug_info;
    }
}
```

2. **Load class**: within your plugin or theme, ensure the Provider class is loaded. Ideally this is run on the `after_classifai_init` action.

3. **Register the Provider with a Service**: Each Provider needs to be registered to one or more of the Services that ClassifAI supports. This can be done using the appropriate filter of the Service you're targeting.

```php
/**
 * Register a new Provider for the Image Processing Service.
 */
add_filter(
    'classifai_image_processing_service_providers',
    function ( $providers ) {
        // Ensure the file that contains the Provider is included or this will throw an error.
        $providers[] = MyPluginOrTheme\MyProvider::class;
        return $providers;
    }
);

/**
 * Register a new Provider for the Language Processing Service.
 */
add_filter(
    'classifai_language_processing_service_providers',
    function ( $providers ) {
        // Ensure the file that contains the Provider is included or this will throw an error.
        $providers[] = MyPluginOrTheme\MyProvider::class;
        return $providers;
    }
);
```

4. **Register the Provider with a Feature**: Each Provider needs to be registered to one or more of the Features that ClassifAI supports. This can be done using the appropriate filter of the Feature you're targeting.

```php
/**
 * Register a new Provider for the Title Generation Feature.
 */
add_filter(
    'classifai_feature_title_generation_providers',
    function ( $providers ) {
        $providers['my_provider'] = __( 'Custom Provider', 'text-domain' );
        return $providers;
    }
);
```

## Add a new Feature

Starting in ClassifAI 3.0.0, it is easier to add your own Features. Most of the implementation details are left to you but there are a few key steps that need to be followed:

1. **Create a new Feature class**: This class should extend the base ClassifAI `Feature` class and setup all required methods.

```php
namespace MyPluginOrTheme;

use Classifai\Features\Feature;
use Classifai\Providers\OpenAI\ChatGPT;
use Classifai\Services\LanguageProcessing;

class MyFeature extends Feature {
    /**
     * ID of the current feature.
     *
     * @var string
     */
    const ID = 'feature_custom';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->label = __( 'Feature label', 'text-domain' );

        // Contains all providers that are registered to the service.
        $this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

        // Contains just the providers this feature supports.
        $this->supported_providers = [
            ChatGPT::ID => __( 'OpenAI ChatGPT', 'text-domain' ),
        ];
    }

    /**
     * Set up necessary hooks.
     *
     * This will always fire even if the Feature is not enabled.
     */
    public function setup() {
        parent::setup();
        add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
    }

    /**
     * Set up necessary hooks.
     *
     * This will only fire if the Feature is enabled.
     */
    public function feature_setup() {
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Register any needed endpoints.
     */
    public function register_endpoints() {
        register_rest_route(
            'my-namespace/v1',
            'custom-feature(?:/(?P<id>\d+))?',
        );
    }

    /**
     * Generic request handler for all our custom routes.
     *
     * @param \WP_REST_Request $request The full request object.
     * @return \WP_REST_Response
     */
    public function rest_endpoint_callback( \WP_REST_Request $request ) {
        $route = $request->get_route();

        if ( strpos( $route, '/custom-feature' ) === 0 ) {
            return rest_ensure_response(
                $this->run(
                    $request->get_param( 'id' ),
                    'custom-route',
                    []
                )
            );
        }

        return parent::rest_endpoint_callback( $request );
    }

    /**
     * Enqueue the editor scripts.
     */
    public function enqueue_editor_assets() {
    }

    /**
     * Enqueue the admin scripts.
     *
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_admin_assets( string $hook_suffix ) {
    }

    /**
     * Get the description for the enable field.
     *
     * @return string
     */
    public function get_enable_description(): string {
        return esc_html__( 'Enable this feature', 'text-domain' );
    }

    /**
     * Add any needed custom fields.
     */
    public function add_custom_settings_fields() {
        $settings = $this->get_settings();

        add_settings_field(
            'custom_setting',
            esc_html__( 'Custom setting', 'classifai' ),
            [ $this, 'render_input' ],
            $this->get_option_name(),
            $this->get_option_name() . '_section',
            [
                'label_for'     => 'custom_setting',
                'placeholder'   => esc_html__( 'Custom setting', 'text-domain' ),
                'default_value' => $settings['custom_setting'],
                'description'   => esc_html__( 'Add a custom setting.', 'text-domain' ),
            ]
        );
    }

    /**
     * Returns the default settings for the feature.
     *
     * @return array
     */
    public function get_feature_default_settings(): array {
        return [
            'custom_setting' => '',
            'provider'       => ChatGPT::ID,
        ];
    }

    /**
     * Sanitizes the default feature settings.
     *
     * @param array $new_settings Settings being saved.
     * @return array
     */
    public function sanitize_default_feature_settings( array $new_settings ): array {
        $new_settings['custom_setting'] = sanitize_text_field( $new_settings['custom_setting'] ?? '' );

        return $new_settings;
    }
}
```

2. **Load class**: within your plugin or theme, ensure the Feature class is loaded. Ideally this is run on the `after_classifai_init` action.

3. **Register the Feature with a Service**: Each Feature needs to be registered to one or more of the Services that ClassifAI supports. This can be done using the appropriate filter of the Service you're targeting.

```php
/**
 * Register a new Feature for the Image Processing Service.
 */
add_filter(
    'image_processing_features',
    function ( $features ) {
        // Ensure the file that contains the Feature is included or this won't work.
        $features[] = MyPluginOrTheme\MyFeature::class;
        return $features;
    }
);

/**
 * Register a new Feature for the Language Processing Service.
 */
add_filter(
    'language_processing_features',
    function ( $features ) {
        // Ensure the file that contains the Feature is included or this won't work.
        $features[] = MyPluginOrTheme\MyFeature::class;
        return $features;
    }
);
```

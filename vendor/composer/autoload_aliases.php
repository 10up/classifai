<?php

// Functions and constants

namespace {
    if(!function_exists('\\getallheaders')){
        function getallheaders(...$args) {
            return \classifaivendor_getallheaders(...func_get_args());
        }
    }
    if(!function_exists('\\trigger_deprecation')){
        function trigger_deprecation(...$args) {
            return \classifaivendor_trigger_deprecation(...func_get_args());
        }
    }

}
namespace Aws {
    if(!function_exists('\\Aws\\constantly')){
        function constantly(...$args) {
            return \ClassifaiVendor\Aws\constantly(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\filter')){
        function filter(...$args) {
            return \ClassifaiVendor\Aws\filter(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\map')){
        function map(...$args) {
            return \ClassifaiVendor\Aws\map(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\flatmap')){
        function flatmap(...$args) {
            return \ClassifaiVendor\Aws\flatmap(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\partition')){
        function partition(...$args) {
            return \ClassifaiVendor\Aws\partition(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\or_chain')){
        function or_chain(...$args) {
            return \ClassifaiVendor\Aws\or_chain(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\load_compiled_json')){
        function load_compiled_json(...$args) {
            return \ClassifaiVendor\Aws\load_compiled_json(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\clear_compiled_json')){
        function clear_compiled_json(...$args) {
            return \ClassifaiVendor\Aws\clear_compiled_json(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\dir_iterator')){
        function dir_iterator(...$args) {
            return \ClassifaiVendor\Aws\dir_iterator(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\recursive_dir_iterator')){
        function recursive_dir_iterator(...$args) {
            return \ClassifaiVendor\Aws\recursive_dir_iterator(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\describe_type')){
        function describe_type(...$args) {
            return \ClassifaiVendor\Aws\describe_type(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\default_http_handler')){
        function default_http_handler(...$args) {
            return \ClassifaiVendor\Aws\default_http_handler(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\default_user_agent')){
        function default_user_agent(...$args) {
            return \ClassifaiVendor\Aws\default_user_agent(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\guzzle_major_version')){
        function guzzle_major_version(...$args) {
            return \ClassifaiVendor\Aws\guzzle_major_version(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\serialize')){
        function serialize(...$args) {
            return \ClassifaiVendor\Aws\serialize(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\manifest')){
        function manifest(...$args) {
            return \ClassifaiVendor\Aws\manifest(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\is_valid_hostname')){
        function is_valid_hostname(...$args) {
            return \ClassifaiVendor\Aws\is_valid_hostname(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\is_valid_hostlabel')){
        function is_valid_hostlabel(...$args) {
            return \ClassifaiVendor\Aws\is_valid_hostlabel(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\parse_ini_file')){
        function parse_ini_file(...$args) {
            return \ClassifaiVendor\Aws\parse_ini_file(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\boolean_value')){
        function boolean_value(...$args) {
            return \ClassifaiVendor\Aws\boolean_value(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\parse_ini_section_with_subsections')){
        function parse_ini_section_with_subsections(...$args) {
            return \ClassifaiVendor\Aws\parse_ini_section_with_subsections(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\is_valid_epoch')){
        function is_valid_epoch(...$args) {
            return \ClassifaiVendor\Aws\is_valid_epoch(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\is_fips_pseudo_region')){
        function is_fips_pseudo_region(...$args) {
            return \ClassifaiVendor\Aws\is_fips_pseudo_region(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\strip_fips_pseudo_regions')){
        function strip_fips_pseudo_regions(...$args) {
            return \ClassifaiVendor\Aws\strip_fips_pseudo_regions(...func_get_args());
        }
    }
    if(!function_exists('\\Aws\\is_associative')){
        function is_associative(...$args) {
            return \ClassifaiVendor\Aws\is_associative(...func_get_args());
        }
    }
}
namespace GuzzleHttp {
    if(!function_exists('\\GuzzleHttp\\describe_type')){
        function describe_type(...$args) {
            return \ClassifaiVendor\GuzzleHttp\describe_type(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\headers_from_lines')){
        function headers_from_lines(...$args) {
            return \ClassifaiVendor\GuzzleHttp\headers_from_lines(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\debug_resource')){
        function debug_resource(...$args) {
            return \ClassifaiVendor\GuzzleHttp\debug_resource(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\choose_handler')){
        function choose_handler(...$args) {
            return \ClassifaiVendor\GuzzleHttp\choose_handler(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\default_user_agent')){
        function default_user_agent(...$args) {
            return \ClassifaiVendor\GuzzleHttp\default_user_agent(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\default_ca_bundle')){
        function default_ca_bundle(...$args) {
            return \ClassifaiVendor\GuzzleHttp\default_ca_bundle(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\normalize_header_keys')){
        function normalize_header_keys(...$args) {
            return \ClassifaiVendor\GuzzleHttp\normalize_header_keys(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\is_host_in_noproxy')){
        function is_host_in_noproxy(...$args) {
            return \ClassifaiVendor\GuzzleHttp\is_host_in_noproxy(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\json_decode')){
        function json_decode(...$args) {
            return \ClassifaiVendor\GuzzleHttp\json_decode(...func_get_args());
        }
    }
    if(!function_exists('\\GuzzleHttp\\json_encode')){
        function json_encode(...$args) {
            return \ClassifaiVendor\GuzzleHttp\json_encode(...func_get_args());
        }
    }
}
namespace JmesPath {
    if(!function_exists('\\JmesPath\\search')){
        function search(...$args) {
            return \ClassifaiVendor\JmesPath\search(...func_get_args());
        }
    }
}


namespace ClassifaiVendor {

    use BrianHenryIE\Strauss\Types\AutoloadAliasInterface;

    /**
     * @see AutoloadAliasInterface
     *
     * @phpstan-type ClassAliasArray array{'type':'class',isabstract:bool,classname:string,namespace?:string,extends:string,implements:array<string>}
     * @phpstan-type InterfaceAliasArray array{'type':'interface',interfacename:string,namespace?:string,extends:array<string>}
     * @phpstan-type TraitAliasArray array{'type':'trait',traitname:string,namespace?:string,use:array<string>}
     * @phpstan-type AutoloadAliasArray array<string,ClassAliasArray|InterfaceAliasArray|TraitAliasArray>
     */
    class AliasAutoloader
    {
        private string $includeFilePath;

        /**
         * @var AutoloadAliasArray
         */
        private array $autoloadAliases = array (
  'AWS\\CRT\\Auth\\AwsCredentials' => 
  array (
    'type' => 'class',
    'classname' => 'AwsCredentials',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\AwsCredentials',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\CredentialsProvider' => 
  array (
    'type' => 'class',
    'classname' => 'CredentialsProvider',
    'isabstract' => true,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\CredentialsProvider',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\Signable' => 
  array (
    'type' => 'class',
    'classname' => 'Signable',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\Signable',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\SignatureType' => 
  array (
    'type' => 'class',
    'classname' => 'SignatureType',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\SignatureType',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\SignedBodyHeaderType' => 
  array (
    'type' => 'class',
    'classname' => 'SignedBodyHeaderType',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\SignedBodyHeaderType',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\Signing' => 
  array (
    'type' => 'class',
    'classname' => 'Signing',
    'isabstract' => true,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\Signing',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\SigningAlgorithm' => 
  array (
    'type' => 'class',
    'classname' => 'SigningAlgorithm',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\SigningAlgorithm',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\SigningConfigAWS' => 
  array (
    'type' => 'class',
    'classname' => 'SigningConfigAWS',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\SigningConfigAWS',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\SigningResult' => 
  array (
    'type' => 'class',
    'classname' => 'SigningResult',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\SigningResult',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Auth\\StaticCredentialsProvider' => 
  array (
    'type' => 'class',
    'classname' => 'StaticCredentialsProvider',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Auth',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Auth\\StaticCredentialsProvider',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\CRT' => 
  array (
    'type' => 'class',
    'classname' => 'CRT',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\CRT',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\HTTP\\Headers' => 
  array (
    'type' => 'class',
    'classname' => 'Headers',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\HTTP',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\HTTP\\Headers',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\HTTP\\Message' => 
  array (
    'type' => 'class',
    'classname' => 'Message',
    'isabstract' => true,
    'namespace' => 'AWS\\CRT\\HTTP',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\HTTP\\Message',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\HTTP\\Request' => 
  array (
    'type' => 'class',
    'classname' => 'Request',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\HTTP',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\HTTP\\Request',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\HTTP\\Response' => 
  array (
    'type' => 'class',
    'classname' => 'Response',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\HTTP',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\HTTP\\Response',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\IO\\EventLoopGroup' => 
  array (
    'type' => 'class',
    'classname' => 'EventLoopGroup',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\IO',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\IO\\EventLoopGroup',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\IO\\InputStream' => 
  array (
    'type' => 'class',
    'classname' => 'InputStream',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\IO',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\IO\\InputStream',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Internal\\Encoding' => 
  array (
    'type' => 'class',
    'classname' => 'Encoding',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Internal',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Internal\\Encoding',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Internal\\Extension' => 
  array (
    'type' => 'class',
    'classname' => 'Extension',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT\\Internal',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Internal\\Extension',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Log' => 
  array (
    'type' => 'class',
    'classname' => 'Log',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Log',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\NativeResource' => 
  array (
    'type' => 'class',
    'classname' => 'NativeResource',
    'isabstract' => true,
    'namespace' => 'AWS\\CRT',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\NativeResource',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\OptionValue' => 
  array (
    'type' => 'class',
    'classname' => 'OptionValue',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\OptionValue',
    'implements' => 
    array (
    ),
  ),
  'AWS\\CRT\\Options' => 
  array (
    'type' => 'class',
    'classname' => 'Options',
    'isabstract' => false,
    'namespace' => 'AWS\\CRT',
    'extends' => 'ClassifaiVendor\\AWS\\CRT\\Options',
    'implements' => 
    array (
    ),
  ),
  'Aws\\AbstractConfigurationProvider' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractConfigurationProvider',
    'isabstract' => true,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\AbstractConfigurationProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\AbstractModel' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractModel',
    'isabstract' => true,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\AbstractModel',
    'implements' => 
    array (
      0 => 'ArrayAccess',
    ),
  ),
  'Aws\\Api\\DateTimeResult' => 
  array (
    'type' => 'class',
    'classname' => 'DateTimeResult',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\DateTimeResult',
    'implements' => 
    array (
      0 => 'JsonSerializable',
    ),
  ),
  'Aws\\Api\\DocModel' => 
  array (
    'type' => 'class',
    'classname' => 'DocModel',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\DocModel',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\ErrorParser\\AbstractErrorParser' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractErrorParser',
    'isabstract' => true,
    'namespace' => 'Aws\\Api\\ErrorParser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\ErrorParser\\AbstractErrorParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\ErrorParser\\JsonRpcErrorParser' => 
  array (
    'type' => 'class',
    'classname' => 'JsonRpcErrorParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\ErrorParser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\ErrorParser\\JsonRpcErrorParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\ErrorParser\\RestJsonErrorParser' => 
  array (
    'type' => 'class',
    'classname' => 'RestJsonErrorParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\ErrorParser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\ErrorParser\\RestJsonErrorParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\ErrorParser\\XmlErrorParser' => 
  array (
    'type' => 'class',
    'classname' => 'XmlErrorParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\ErrorParser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\ErrorParser\\XmlErrorParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\ListShape' => 
  array (
    'type' => 'class',
    'classname' => 'ListShape',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\ListShape',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\MapShape' => 
  array (
    'type' => 'class',
    'classname' => 'MapShape',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\MapShape',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Operation' => 
  array (
    'type' => 'class',
    'classname' => 'Operation',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Operation',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\AbstractParser' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractParser',
    'isabstract' => true,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\AbstractParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\AbstractRestParser' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractRestParser',
    'isabstract' => true,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\AbstractRestParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\Crc32ValidatingParser' => 
  array (
    'type' => 'class',
    'classname' => 'Crc32ValidatingParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\Crc32ValidatingParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\DecodingEventStreamIterator' => 
  array (
    'type' => 'class',
    'classname' => 'DecodingEventStreamIterator',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\DecodingEventStreamIterator',
    'implements' => 
    array (
      0 => 'Iterator',
    ),
  ),
  'Aws\\Api\\Parser\\EventParsingIterator' => 
  array (
    'type' => 'class',
    'classname' => 'EventParsingIterator',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\EventParsingIterator',
    'implements' => 
    array (
      0 => 'Iterator',
    ),
  ),
  'Aws\\Api\\Parser\\Exception\\ParserException' => 
  array (
    'type' => 'class',
    'classname' => 'ParserException',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\Exception\\ParserException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
      1 => 'Aws\\ResponseContainerInterface',
    ),
  ),
  'Aws\\Api\\Parser\\JsonParser' => 
  array (
    'type' => 'class',
    'classname' => 'JsonParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\JsonParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\JsonRpcParser' => 
  array (
    'type' => 'class',
    'classname' => 'JsonRpcParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\JsonRpcParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\NonSeekableStreamDecodingEventStreamIterator' => 
  array (
    'type' => 'class',
    'classname' => 'NonSeekableStreamDecodingEventStreamIterator',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\NonSeekableStreamDecodingEventStreamIterator',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\QueryParser' => 
  array (
    'type' => 'class',
    'classname' => 'QueryParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\QueryParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\RestJsonParser' => 
  array (
    'type' => 'class',
    'classname' => 'RestJsonParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\RestJsonParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\RestXmlParser' => 
  array (
    'type' => 'class',
    'classname' => 'RestXmlParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\RestXmlParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Parser\\XmlParser' => 
  array (
    'type' => 'class',
    'classname' => 'XmlParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Parser\\XmlParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\Ec2ParamBuilder' => 
  array (
    'type' => 'class',
    'classname' => 'Ec2ParamBuilder',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\Ec2ParamBuilder',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\JsonBody' => 
  array (
    'type' => 'class',
    'classname' => 'JsonBody',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\JsonBody',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\JsonRpcSerializer' => 
  array (
    'type' => 'class',
    'classname' => 'JsonRpcSerializer',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\JsonRpcSerializer',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\QueryParamBuilder' => 
  array (
    'type' => 'class',
    'classname' => 'QueryParamBuilder',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\QueryParamBuilder',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\QuerySerializer' => 
  array (
    'type' => 'class',
    'classname' => 'QuerySerializer',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\QuerySerializer',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\RestJsonSerializer' => 
  array (
    'type' => 'class',
    'classname' => 'RestJsonSerializer',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\RestJsonSerializer',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\RestSerializer' => 
  array (
    'type' => 'class',
    'classname' => 'RestSerializer',
    'isabstract' => true,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\RestSerializer',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\RestXmlSerializer' => 
  array (
    'type' => 'class',
    'classname' => 'RestXmlSerializer',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\RestXmlSerializer',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Serializer\\XmlBody' => 
  array (
    'type' => 'class',
    'classname' => 'XmlBody',
    'isabstract' => false,
    'namespace' => 'Aws\\Api\\Serializer',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Serializer\\XmlBody',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Service' => 
  array (
    'type' => 'class',
    'classname' => 'Service',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Service',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Shape' => 
  array (
    'type' => 'class',
    'classname' => 'Shape',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Shape',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\ShapeMap' => 
  array (
    'type' => 'class',
    'classname' => 'ShapeMap',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\ShapeMap',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\StructureShape' => 
  array (
    'type' => 'class',
    'classname' => 'StructureShape',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\StructureShape',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\TimestampShape' => 
  array (
    'type' => 'class',
    'classname' => 'TimestampShape',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\TimestampShape',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Api\\Validator' => 
  array (
    'type' => 'class',
    'classname' => 'Validator',
    'isabstract' => false,
    'namespace' => 'Aws\\Api',
    'extends' => 'ClassifaiVendor\\Aws\\Api\\Validator',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Arn\\AccessPointArn' => 
  array (
    'type' => 'class',
    'classname' => 'AccessPointArn',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\AccessPointArn',
    'implements' => 
    array (
      0 => 'Aws\\Arn\\AccessPointArnInterface',
    ),
  ),
  'Aws\\Arn\\Arn' => 
  array (
    'type' => 'class',
    'classname' => 'Arn',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\Arn',
    'implements' => 
    array (
      0 => 'Aws\\Arn\\ArnInterface',
    ),
  ),
  'Aws\\Arn\\ArnParser' => 
  array (
    'type' => 'class',
    'classname' => 'ArnParser',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\ArnParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Arn\\Exception\\InvalidArnException' => 
  array (
    'type' => 'class',
    'classname' => 'InvalidArnException',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\Exception\\InvalidArnException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Arn\\ObjectLambdaAccessPointArn' => 
  array (
    'type' => 'class',
    'classname' => 'ObjectLambdaAccessPointArn',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\ObjectLambdaAccessPointArn',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Arn\\S3\\AccessPointArn' => 
  array (
    'type' => 'class',
    'classname' => 'AccessPointArn',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\S3\\AccessPointArn',
    'implements' => 
    array (
      0 => 'Aws\\Arn\\AccessPointArnInterface',
    ),
  ),
  'Aws\\Arn\\S3\\MultiRegionAccessPointArn' => 
  array (
    'type' => 'class',
    'classname' => 'MultiRegionAccessPointArn',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\S3\\MultiRegionAccessPointArn',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Arn\\S3\\OutpostsAccessPointArn' => 
  array (
    'type' => 'class',
    'classname' => 'OutpostsAccessPointArn',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\S3\\OutpostsAccessPointArn',
    'implements' => 
    array (
      0 => 'Aws\\Arn\\AccessPointArnInterface',
      1 => 'Aws\\Arn\\S3\\OutpostsArnInterface',
    ),
  ),
  'Aws\\Arn\\S3\\OutpostsBucketArn' => 
  array (
    'type' => 'class',
    'classname' => 'OutpostsBucketArn',
    'isabstract' => false,
    'namespace' => 'Aws\\Arn\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\Arn\\S3\\OutpostsBucketArn',
    'implements' => 
    array (
      0 => 'Aws\\Arn\\S3\\BucketArnInterface',
      1 => 'Aws\\Arn\\S3\\OutpostsArnInterface',
    ),
  ),
  'Aws\\Auth\\AuthSchemeResolver' => 
  array (
    'type' => 'class',
    'classname' => 'AuthSchemeResolver',
    'isabstract' => false,
    'namespace' => 'Aws\\Auth',
    'extends' => 'ClassifaiVendor\\Aws\\Auth\\AuthSchemeResolver',
    'implements' => 
    array (
      0 => 'Aws\\Auth\\AuthSchemeResolverInterface',
    ),
  ),
  'Aws\\Auth\\AuthSelectionMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'AuthSelectionMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\Auth',
    'extends' => 'ClassifaiVendor\\Aws\\Auth\\AuthSelectionMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Auth\\Exception\\UnresolvedAuthSchemeException' => 
  array (
    'type' => 'class',
    'classname' => 'UnresolvedAuthSchemeException',
    'isabstract' => false,
    'namespace' => 'Aws\\Auth\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Auth\\Exception\\UnresolvedAuthSchemeException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\AwsClient' => 
  array (
    'type' => 'class',
    'classname' => 'AwsClient',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\AwsClient',
    'implements' => 
    array (
      0 => 'Aws\\AwsClientInterface',
    ),
  ),
  'Aws\\ClientResolver' => 
  array (
    'type' => 'class',
    'classname' => 'ClientResolver',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\ClientResolver',
    'implements' => 
    array (
    ),
  ),
  'Aws\\ClientSideMonitoring\\AbstractMonitoringMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractMonitoringMiddleware',
    'isabstract' => true,
    'namespace' => 'Aws\\ClientSideMonitoring',
    'extends' => 'ClassifaiVendor\\Aws\\ClientSideMonitoring\\AbstractMonitoringMiddleware',
    'implements' => 
    array (
      0 => 'Aws\\ClientSideMonitoring\\MonitoringMiddlewareInterface',
    ),
  ),
  'Aws\\ClientSideMonitoring\\ApiCallAttemptMonitoringMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'ApiCallAttemptMonitoringMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\ClientSideMonitoring',
    'extends' => 'ClassifaiVendor\\Aws\\ClientSideMonitoring\\ApiCallAttemptMonitoringMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\ClientSideMonitoring\\ApiCallMonitoringMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'ApiCallMonitoringMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\ClientSideMonitoring',
    'extends' => 'ClassifaiVendor\\Aws\\ClientSideMonitoring\\ApiCallMonitoringMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\ClientSideMonitoring\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\ClientSideMonitoring',
    'extends' => 'ClassifaiVendor\\Aws\\ClientSideMonitoring\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\ClientSideMonitoring\\ConfigurationInterface',
    ),
  ),
  'Aws\\ClientSideMonitoring\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\ClientSideMonitoring\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\ClientSideMonitoring\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Command' => 
  array (
    'type' => 'class',
    'classname' => 'Command',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\Command',
    'implements' => 
    array (
      0 => 'Aws\\CommandInterface',
    ),
  ),
  'Aws\\CommandPool' => 
  array (
    'type' => 'class',
    'classname' => 'CommandPool',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\CommandPool',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'Aws\\Configuration\\ConfigurationResolver' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationResolver',
    'isabstract' => false,
    'namespace' => 'Aws\\Configuration',
    'extends' => 'ClassifaiVendor\\Aws\\Configuration\\ConfigurationResolver',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Credentials\\AssumeRoleCredentialProvider' => 
  array (
    'type' => 'class',
    'classname' => 'AssumeRoleCredentialProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Credentials',
    'extends' => 'ClassifaiVendor\\Aws\\Credentials\\AssumeRoleCredentialProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Credentials\\AssumeRoleWithWebIdentityCredentialProvider' => 
  array (
    'type' => 'class',
    'classname' => 'AssumeRoleWithWebIdentityCredentialProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Credentials',
    'extends' => 'ClassifaiVendor\\Aws\\Credentials\\AssumeRoleWithWebIdentityCredentialProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Credentials\\CredentialSources' => 
  array (
    'type' => 'class',
    'classname' => 'CredentialSources',
    'isabstract' => false,
    'namespace' => 'Aws\\Credentials',
    'extends' => 'ClassifaiVendor\\Aws\\Credentials\\CredentialSources',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Credentials\\Credentials' => 
  array (
    'type' => 'class',
    'classname' => 'Credentials',
    'isabstract' => false,
    'namespace' => 'Aws\\Credentials',
    'extends' => 'ClassifaiVendor\\Aws\\Credentials\\Credentials',
    'implements' => 
    array (
      0 => 'Aws\\Credentials\\CredentialsInterface',
      1 => 'Serializable',
    ),
  ),
  'Aws\\Credentials\\CredentialsUtils' => 
  array (
    'type' => 'class',
    'classname' => 'CredentialsUtils',
    'isabstract' => false,
    'namespace' => 'Aws\\Credentials',
    'extends' => 'ClassifaiVendor\\Aws\\Credentials\\CredentialsUtils',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Credentials\\EcsCredentialProvider' => 
  array (
    'type' => 'class',
    'classname' => 'EcsCredentialProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Credentials',
    'extends' => 'ClassifaiVendor\\Aws\\Credentials\\EcsCredentialProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Credentials\\InstanceProfileProvider' => 
  array (
    'type' => 'class',
    'classname' => 'InstanceProfileProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Credentials',
    'extends' => 'ClassifaiVendor\\Aws\\Credentials\\InstanceProfileProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Crypto\\AbstractCryptoClient' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractCryptoClient',
    'isabstract' => true,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\AbstractCryptoClient',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Crypto\\AbstractCryptoClientV2' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractCryptoClientV2',
    'isabstract' => true,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\AbstractCryptoClientV2',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Crypto\\AesDecryptingStream' => 
  array (
    'type' => 'class',
    'classname' => 'AesDecryptingStream',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\AesDecryptingStream',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\AesStreamInterface',
    ),
  ),
  'Aws\\Crypto\\AesEncryptingStream' => 
  array (
    'type' => 'class',
    'classname' => 'AesEncryptingStream',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\AesEncryptingStream',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\AesStreamInterface',
    ),
  ),
  'Aws\\Crypto\\AesGcmDecryptingStream' => 
  array (
    'type' => 'class',
    'classname' => 'AesGcmDecryptingStream',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\AesGcmDecryptingStream',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\AesStreamInterface',
    ),
  ),
  'Aws\\Crypto\\AesGcmEncryptingStream' => 
  array (
    'type' => 'class',
    'classname' => 'AesGcmEncryptingStream',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\AesGcmEncryptingStream',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\AesStreamInterface',
      1 => 'Aws\\Crypto\\AesStreamInterfaceV2',
    ),
  ),
  'Aws\\Crypto\\Cipher\\Cbc' => 
  array (
    'type' => 'class',
    'classname' => 'Cbc',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto\\Cipher',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\Cipher\\Cbc',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\Cipher\\CipherMethod',
    ),
  ),
  'Aws\\Crypto\\KmsMaterialsProvider' => 
  array (
    'type' => 'class',
    'classname' => 'KmsMaterialsProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\KmsMaterialsProvider',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\MaterialsProviderInterface',
    ),
  ),
  'Aws\\Crypto\\KmsMaterialsProviderV2' => 
  array (
    'type' => 'class',
    'classname' => 'KmsMaterialsProviderV2',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\KmsMaterialsProviderV2',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\MaterialsProviderInterfaceV2',
    ),
  ),
  'Aws\\Crypto\\MaterialsProvider' => 
  array (
    'type' => 'class',
    'classname' => 'MaterialsProvider',
    'isabstract' => true,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\MaterialsProvider',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\MaterialsProviderInterface',
    ),
  ),
  'Aws\\Crypto\\MaterialsProviderV2' => 
  array (
    'type' => 'class',
    'classname' => 'MaterialsProviderV2',
    'isabstract' => true,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\MaterialsProviderV2',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\MaterialsProviderInterfaceV2',
    ),
  ),
  'Aws\\Crypto\\MetadataEnvelope' => 
  array (
    'type' => 'class',
    'classname' => 'MetadataEnvelope',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\MetadataEnvelope',
    'implements' => 
    array (
      0 => 'ArrayAccess',
      1 => 'IteratorAggregate',
      2 => 'JsonSerializable',
    ),
  ),
  'Aws\\Crypto\\Polyfill\\AesGcm' => 
  array (
    'type' => 'class',
    'classname' => 'AesGcm',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto\\Polyfill',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\Polyfill\\AesGcm',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Crypto\\Polyfill\\ByteArray' => 
  array (
    'type' => 'class',
    'classname' => 'ByteArray',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto\\Polyfill',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\Polyfill\\ByteArray',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Crypto\\Polyfill\\Gmac' => 
  array (
    'type' => 'class',
    'classname' => 'Gmac',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto\\Polyfill',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\Polyfill\\Gmac',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Crypto\\Polyfill\\Key' => 
  array (
    'type' => 'class',
    'classname' => 'Key',
    'isabstract' => false,
    'namespace' => 'Aws\\Crypto\\Polyfill',
    'extends' => 'ClassifaiVendor\\Aws\\Crypto\\Polyfill\\Key',
    'implements' => 
    array (
    ),
  ),
  'Aws\\DefaultsMode\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\DefaultsMode',
    'extends' => 'ClassifaiVendor\\Aws\\DefaultsMode\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\DefaultsMode\\ConfigurationInterface',
    ),
  ),
  'Aws\\DefaultsMode\\ConfigurationProvider' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\DefaultsMode',
    'extends' => 'ClassifaiVendor\\Aws\\DefaultsMode\\ConfigurationProvider',
    'implements' => 
    array (
      0 => 'Aws\\ConfigurationProviderInterface',
    ),
  ),
  'Aws\\DefaultsMode\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\DefaultsMode\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\DefaultsMode\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\DoctrineCacheAdapter' => 
  array (
    'type' => 'class',
    'classname' => 'DoctrineCacheAdapter',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\DoctrineCacheAdapter',
    'implements' => 
    array (
      0 => 'Aws\\CacheInterface',
      1 => 'Doctrine\\Common\\Cache\\Cache',
    ),
  ),
  'Aws\\Endpoint\\Partition' => 
  array (
    'type' => 'class',
    'classname' => 'Partition',
    'isabstract' => false,
    'namespace' => 'Aws\\Endpoint',
    'extends' => 'ClassifaiVendor\\Aws\\Endpoint\\Partition',
    'implements' => 
    array (
      0 => 'ArrayAccess',
      1 => 'Aws\\Endpoint\\PartitionInterface',
    ),
  ),
  'Aws\\Endpoint\\PartitionEndpointProvider' => 
  array (
    'type' => 'class',
    'classname' => 'PartitionEndpointProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Endpoint',
    'extends' => 'ClassifaiVendor\\Aws\\Endpoint\\PartitionEndpointProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Endpoint\\PatternEndpointProvider' => 
  array (
    'type' => 'class',
    'classname' => 'PatternEndpointProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Endpoint',
    'extends' => 'ClassifaiVendor\\Aws\\Endpoint\\PatternEndpointProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Endpoint\\UseDualstackEndpoint\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\Endpoint\\UseDualstackEndpoint',
    'extends' => 'ClassifaiVendor\\Aws\\Endpoint\\UseDualstackEndpoint\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\Endpoint\\UseDualstackEndpoint\\ConfigurationInterface',
    ),
  ),
  'Aws\\Endpoint\\UseDualstackEndpoint\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\Endpoint\\UseDualstackEndpoint\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Endpoint\\UseDualstackEndpoint\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Endpoint\\UseFipsEndpoint\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\Endpoint\\UseFipsEndpoint',
    'extends' => 'ClassifaiVendor\\Aws\\Endpoint\\UseFipsEndpoint\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\Endpoint\\UseFipsEndpoint\\ConfigurationInterface',
    ),
  ),
  'Aws\\Endpoint\\UseFipsEndpoint\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\Endpoint\\UseFipsEndpoint\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Endpoint\\UseFipsEndpoint\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\EndpointDiscovery\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointDiscovery',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointDiscovery\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\EndpointDiscovery\\ConfigurationInterface',
    ),
  ),
  'Aws\\EndpointDiscovery\\EndpointDiscoveryMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointDiscoveryMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointDiscovery',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointDiscovery\\EndpointDiscoveryMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointDiscovery\\EndpointList' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointList',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointDiscovery',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointDiscovery\\EndpointList',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointDiscovery\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointDiscovery\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointDiscovery\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\EndpointParameterMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointParameterMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointParameterMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\EndpointDefinitionProvider' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointDefinitionProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\EndpointDefinitionProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\EndpointProviderV2' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointProviderV2',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\EndpointProviderV2',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\EndpointV2Middleware' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointV2Middleware',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\EndpointV2Middleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Rule\\AbstractRule' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractRule',
    'isabstract' => true,
    'namespace' => 'Aws\\EndpointV2\\Rule',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Rule\\AbstractRule',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Rule\\EndpointRule' => 
  array (
    'type' => 'class',
    'classname' => 'EndpointRule',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Rule',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Rule\\EndpointRule',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Rule\\ErrorRule' => 
  array (
    'type' => 'class',
    'classname' => 'ErrorRule',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Rule',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Rule\\ErrorRule',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Rule\\RuleCreator' => 
  array (
    'type' => 'class',
    'classname' => 'RuleCreator',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Rule',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Rule\\RuleCreator',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Rule\\TreeRule' => 
  array (
    'type' => 'class',
    'classname' => 'TreeRule',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Rule',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Rule\\TreeRule',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Ruleset\\Ruleset' => 
  array (
    'type' => 'class',
    'classname' => 'Ruleset',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Ruleset',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Ruleset\\Ruleset',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Ruleset\\RulesetEndpoint' => 
  array (
    'type' => 'class',
    'classname' => 'RulesetEndpoint',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Ruleset',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Ruleset\\RulesetEndpoint',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Ruleset\\RulesetParameter' => 
  array (
    'type' => 'class',
    'classname' => 'RulesetParameter',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Ruleset',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Ruleset\\RulesetParameter',
    'implements' => 
    array (
    ),
  ),
  'Aws\\EndpointV2\\Ruleset\\RulesetStandardLibrary' => 
  array (
    'type' => 'class',
    'classname' => 'RulesetStandardLibrary',
    'isabstract' => false,
    'namespace' => 'Aws\\EndpointV2\\Ruleset',
    'extends' => 'ClassifaiVendor\\Aws\\EndpointV2\\Ruleset\\RulesetStandardLibrary',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Exception\\AwsException' => 
  array (
    'type' => 'class',
    'classname' => 'AwsException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\AwsException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
      1 => 'Aws\\ResponseContainerInterface',
      2 => 'ArrayAccess',
    ),
  ),
  'Aws\\Exception\\CommonRuntimeException' => 
  array (
    'type' => 'class',
    'classname' => 'CommonRuntimeException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\CommonRuntimeException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Exception\\CouldNotCreateChecksumException' => 
  array (
    'type' => 'class',
    'classname' => 'CouldNotCreateChecksumException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\CouldNotCreateChecksumException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\CredentialsException' => 
  array (
    'type' => 'class',
    'classname' => 'CredentialsException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\CredentialsException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\CryptoException' => 
  array (
    'type' => 'class',
    'classname' => 'CryptoException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\CryptoException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Exception\\CryptoPolyfillException' => 
  array (
    'type' => 'class',
    'classname' => 'CryptoPolyfillException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\CryptoPolyfillException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Exception\\EventStreamDataException' => 
  array (
    'type' => 'class',
    'classname' => 'EventStreamDataException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\EventStreamDataException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Exception\\IncalculablePayloadException' => 
  array (
    'type' => 'class',
    'classname' => 'IncalculablePayloadException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\IncalculablePayloadException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\InvalidJsonException' => 
  array (
    'type' => 'class',
    'classname' => 'InvalidJsonException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\InvalidJsonException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\InvalidRegionException' => 
  array (
    'type' => 'class',
    'classname' => 'InvalidRegionException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\InvalidRegionException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\MultipartUploadException' => 
  array (
    'type' => 'class',
    'classname' => 'MultipartUploadException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\MultipartUploadException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\TokenException' => 
  array (
    'type' => 'class',
    'classname' => 'TokenException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\TokenException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\UnresolvedApiException' => 
  array (
    'type' => 'class',
    'classname' => 'UnresolvedApiException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\UnresolvedApiException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\UnresolvedEndpointException' => 
  array (
    'type' => 'class',
    'classname' => 'UnresolvedEndpointException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\UnresolvedEndpointException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Exception\\UnresolvedSignatureException' => 
  array (
    'type' => 'class',
    'classname' => 'UnresolvedSignatureException',
    'isabstract' => false,
    'namespace' => 'Aws\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Exception\\UnresolvedSignatureException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Handler\\GuzzleV5\\GuzzleHandler' => 
  array (
    'type' => 'class',
    'classname' => 'GuzzleHandler',
    'isabstract' => false,
    'namespace' => 'Aws\\Handler\\GuzzleV5',
    'extends' => 'ClassifaiVendor\\Aws\\Handler\\GuzzleV5\\GuzzleHandler',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Handler\\GuzzleV5\\GuzzleStream' => 
  array (
    'type' => 'class',
    'classname' => 'GuzzleStream',
    'isabstract' => false,
    'namespace' => 'Aws\\Handler\\GuzzleV5',
    'extends' => 'ClassifaiVendor\\Aws\\Handler\\GuzzleV5\\GuzzleStream',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Stream\\StreamInterface',
    ),
  ),
  'Aws\\Handler\\GuzzleV5\\PsrStream' => 
  array (
    'type' => 'class',
    'classname' => 'PsrStream',
    'isabstract' => false,
    'namespace' => 'Aws\\Handler\\GuzzleV5',
    'extends' => 'ClassifaiVendor\\Aws\\Handler\\GuzzleV5\\PsrStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'Aws\\Handler\\GuzzleV6\\GuzzleHandler' => 
  array (
    'type' => 'class',
    'classname' => 'GuzzleHandler',
    'isabstract' => false,
    'namespace' => 'Aws\\Handler\\GuzzleV6',
    'extends' => 'ClassifaiVendor\\Aws\\Handler\\GuzzleV6\\GuzzleHandler',
    'implements' => 
    array (
    ),
  ),
  'Aws\\HandlerList' => 
  array (
    'type' => 'class',
    'classname' => 'HandlerList',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\HandlerList',
    'implements' => 
    array (
      0 => 'Countable',
    ),
  ),
  'Aws\\HashingStream' => 
  array (
    'type' => 'class',
    'classname' => 'HashingStream',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\HashingStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'Aws\\History' => 
  array (
    'type' => 'class',
    'classname' => 'History',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\History',
    'implements' => 
    array (
      0 => 'Countable',
      1 => 'IteratorAggregate',
    ),
  ),
  'Aws\\IdempotencyTokenMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'IdempotencyTokenMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\IdempotencyTokenMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Identity\\AwsCredentialIdentity' => 
  array (
    'type' => 'class',
    'classname' => 'AwsCredentialIdentity',
    'isabstract' => true,
    'namespace' => 'Aws\\Identity',
    'extends' => 'ClassifaiVendor\\Aws\\Identity\\AwsCredentialIdentity',
    'implements' => 
    array (
      0 => 'Aws\\Identity\\IdentityInterface',
    ),
  ),
  'Aws\\Identity\\BearerTokenIdentity' => 
  array (
    'type' => 'class',
    'classname' => 'BearerTokenIdentity',
    'isabstract' => true,
    'namespace' => 'Aws\\Identity',
    'extends' => 'ClassifaiVendor\\Aws\\Identity\\BearerTokenIdentity',
    'implements' => 
    array (
      0 => 'Aws\\Identity\\IdentityInterface',
    ),
  ),
  'Aws\\Identity\\S3\\S3ExpressIdentity' => 
  array (
    'type' => 'class',
    'classname' => 'S3ExpressIdentity',
    'isabstract' => false,
    'namespace' => 'Aws\\Identity\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\Identity\\S3\\S3ExpressIdentity',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Identity\\S3\\S3ExpressIdentityProvider' => 
  array (
    'type' => 'class',
    'classname' => 'S3ExpressIdentityProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Identity\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\Identity\\S3\\S3ExpressIdentityProvider',
    'implements' => 
    array (
    ),
  ),
  'Aws\\InputValidationMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'InputValidationMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\InputValidationMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\JsonCompiler' => 
  array (
    'type' => 'class',
    'classname' => 'JsonCompiler',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\JsonCompiler',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Kms\\Exception\\KmsException' => 
  array (
    'type' => 'class',
    'classname' => 'KmsException',
    'isabstract' => false,
    'namespace' => 'Aws\\Kms\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Kms\\Exception\\KmsException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Kms\\KmsClient' => 
  array (
    'type' => 'class',
    'classname' => 'KmsClient',
    'isabstract' => false,
    'namespace' => 'Aws\\Kms',
    'extends' => 'ClassifaiVendor\\Aws\\Kms\\KmsClient',
    'implements' => 
    array (
    ),
  ),
  'Aws\\LruArrayCache' => 
  array (
    'type' => 'class',
    'classname' => 'LruArrayCache',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\LruArrayCache',
    'implements' => 
    array (
      0 => 'Aws\\CacheInterface',
      1 => 'Countable',
    ),
  ),
  'Aws\\MetricsBuilder' => 
  array (
    'type' => 'class',
    'classname' => 'MetricsBuilder',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\MetricsBuilder',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Middleware' => 
  array (
    'type' => 'class',
    'classname' => 'Middleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\Middleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\MockHandler' => 
  array (
    'type' => 'class',
    'classname' => 'MockHandler',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\MockHandler',
    'implements' => 
    array (
      0 => 'Countable',
    ),
  ),
  'Aws\\MultiRegionClient' => 
  array (
    'type' => 'class',
    'classname' => 'MultiRegionClient',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\MultiRegionClient',
    'implements' => 
    array (
      0 => 'Aws\\AwsClientInterface',
    ),
  ),
  'Aws\\Multipart\\AbstractUploadManager' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractUploadManager',
    'isabstract' => true,
    'namespace' => 'Aws\\Multipart',
    'extends' => 'ClassifaiVendor\\Aws\\Multipart\\AbstractUploadManager',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'Aws\\Multipart\\AbstractUploader' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractUploader',
    'isabstract' => true,
    'namespace' => 'Aws\\Multipart',
    'extends' => 'ClassifaiVendor\\Aws\\Multipart\\AbstractUploader',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Multipart\\UploadState' => 
  array (
    'type' => 'class',
    'classname' => 'UploadState',
    'isabstract' => false,
    'namespace' => 'Aws\\Multipart',
    'extends' => 'ClassifaiVendor\\Aws\\Multipart\\UploadState',
    'implements' => 
    array (
    ),
  ),
  'Aws\\PhpHash' => 
  array (
    'type' => 'class',
    'classname' => 'PhpHash',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\PhpHash',
    'implements' => 
    array (
      0 => 'Aws\\HashInterface',
    ),
  ),
  'Aws\\Polly\\Exception\\PollyException' => 
  array (
    'type' => 'class',
    'classname' => 'PollyException',
    'isabstract' => false,
    'namespace' => 'Aws\\Polly\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Polly\\Exception\\PollyException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Polly\\PollyClient' => 
  array (
    'type' => 'class',
    'classname' => 'PollyClient',
    'isabstract' => false,
    'namespace' => 'Aws\\Polly',
    'extends' => 'ClassifaiVendor\\Aws\\Polly\\PollyClient',
    'implements' => 
    array (
    ),
  ),
  'Aws\\PresignUrlMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'PresignUrlMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\PresignUrlMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Psr16CacheAdapter' => 
  array (
    'type' => 'class',
    'classname' => 'Psr16CacheAdapter',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\Psr16CacheAdapter',
    'implements' => 
    array (
      0 => 'Aws\\CacheInterface',
    ),
  ),
  'Aws\\PsrCacheAdapter' => 
  array (
    'type' => 'class',
    'classname' => 'PsrCacheAdapter',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\PsrCacheAdapter',
    'implements' => 
    array (
      0 => 'Aws\\CacheInterface',
    ),
  ),
  'Aws\\QueryCompatibleInputMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'QueryCompatibleInputMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\QueryCompatibleInputMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\RequestCompressionMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'RequestCompressionMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\RequestCompressionMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Result' => 
  array (
    'type' => 'class',
    'classname' => 'Result',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\Result',
    'implements' => 
    array (
      0 => 'Aws\\ResultInterface',
      1 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\ResultPaginator' => 
  array (
    'type' => 'class',
    'classname' => 'ResultPaginator',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\ResultPaginator',
    'implements' => 
    array (
      0 => 'Iterator',
    ),
  ),
  'Aws\\Retry\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\Retry',
    'extends' => 'ClassifaiVendor\\Aws\\Retry\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\Retry\\ConfigurationInterface',
    ),
  ),
  'Aws\\Retry\\ConfigurationProvider' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Retry',
    'extends' => 'ClassifaiVendor\\Aws\\Retry\\ConfigurationProvider',
    'implements' => 
    array (
      0 => 'Aws\\ConfigurationProviderInterface',
    ),
  ),
  'Aws\\Retry\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\Retry\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Retry\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Retry\\QuotaManager' => 
  array (
    'type' => 'class',
    'classname' => 'QuotaManager',
    'isabstract' => false,
    'namespace' => 'Aws\\Retry',
    'extends' => 'ClassifaiVendor\\Aws\\Retry\\QuotaManager',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Retry\\RateLimiter' => 
  array (
    'type' => 'class',
    'classname' => 'RateLimiter',
    'isabstract' => false,
    'namespace' => 'Aws\\Retry',
    'extends' => 'ClassifaiVendor\\Aws\\Retry\\RateLimiter',
    'implements' => 
    array (
    ),
  ),
  'Aws\\RetryMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'RetryMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\RetryMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\RetryMiddlewareV2' => 
  array (
    'type' => 'class',
    'classname' => 'RetryMiddlewareV2',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\RetryMiddlewareV2',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\AmbiguousSuccessParser' => 
  array (
    'type' => 'class',
    'classname' => 'AmbiguousSuccessParser',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\AmbiguousSuccessParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\ApplyChecksumMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'ApplyChecksumMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\ApplyChecksumMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\BatchDelete' => 
  array (
    'type' => 'class',
    'classname' => 'BatchDelete',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\BatchDelete',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'Aws\\S3\\BucketEndpointArnMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'BucketEndpointArnMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\BucketEndpointArnMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\BucketEndpointMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'BucketEndpointMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\BucketEndpointMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Crypto\\HeadersMetadataStrategy' => 
  array (
    'type' => 'class',
    'classname' => 'HeadersMetadataStrategy',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Crypto\\HeadersMetadataStrategy',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\MetadataStrategyInterface',
    ),
  ),
  'Aws\\S3\\Crypto\\InstructionFileMetadataStrategy' => 
  array (
    'type' => 'class',
    'classname' => 'InstructionFileMetadataStrategy',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Crypto\\InstructionFileMetadataStrategy',
    'implements' => 
    array (
      0 => 'Aws\\Crypto\\MetadataStrategyInterface',
    ),
  ),
  'Aws\\S3\\Crypto\\S3EncryptionClient' => 
  array (
    'type' => 'class',
    'classname' => 'S3EncryptionClient',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Crypto\\S3EncryptionClient',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Crypto\\S3EncryptionMultipartUploader' => 
  array (
    'type' => 'class',
    'classname' => 'S3EncryptionMultipartUploader',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Crypto\\S3EncryptionMultipartUploader',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Crypto\\S3EncryptionMultipartUploaderV2' => 
  array (
    'type' => 'class',
    'classname' => 'S3EncryptionMultipartUploaderV2',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Crypto',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Crypto\\S3EncryptionMultipartUploaderV2',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Exception\\DeleteMultipleObjectsException' => 
  array (
    'type' => 'class',
    'classname' => 'DeleteMultipleObjectsException',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Exception\\DeleteMultipleObjectsException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\S3\\Exception\\PermanentRedirectException' => 
  array (
    'type' => 'class',
    'classname' => 'PermanentRedirectException',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Exception\\PermanentRedirectException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Exception\\S3Exception' => 
  array (
    'type' => 'class',
    'classname' => 'S3Exception',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Exception\\S3Exception',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Exception\\S3MultipartUploadException' => 
  array (
    'type' => 'class',
    'classname' => 'S3MultipartUploadException',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Exception\\S3MultipartUploadException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\ExpiresParsingMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'ExpiresParsingMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\ExpiresParsingMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\GetBucketLocationParser' => 
  array (
    'type' => 'class',
    'classname' => 'GetBucketLocationParser',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\GetBucketLocationParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\MultipartCopy' => 
  array (
    'type' => 'class',
    'classname' => 'MultipartCopy',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\MultipartCopy',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\MultipartUploader' => 
  array (
    'type' => 'class',
    'classname' => 'MultipartUploader',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\MultipartUploader',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\ObjectCopier' => 
  array (
    'type' => 'class',
    'classname' => 'ObjectCopier',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\ObjectCopier',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'Aws\\S3\\ObjectUploader' => 
  array (
    'type' => 'class',
    'classname' => 'ObjectUploader',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\ObjectUploader',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'Aws\\S3\\Parser\\GetBucketLocationResultMutator' => 
  array (
    'type' => 'class',
    'classname' => 'GetBucketLocationResultMutator',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Parser\\GetBucketLocationResultMutator',
    'implements' => 
    array (
      0 => 'Aws\\S3\\Parser\\S3ResultMutator',
    ),
  ),
  'Aws\\S3\\Parser\\S3Parser' => 
  array (
    'type' => 'class',
    'classname' => 'S3Parser',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Parser\\S3Parser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Parser\\ValidateResponseChecksumResultMutator' => 
  array (
    'type' => 'class',
    'classname' => 'ValidateResponseChecksumResultMutator',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\Parser',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Parser\\ValidateResponseChecksumResultMutator',
    'implements' => 
    array (
      0 => 'Aws\\S3\\Parser\\S3ResultMutator',
    ),
  ),
  'Aws\\S3\\PermanentRedirectMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'PermanentRedirectMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\PermanentRedirectMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\PostObject' => 
  array (
    'type' => 'class',
    'classname' => 'PostObject',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\PostObject',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\PostObjectV4' => 
  array (
    'type' => 'class',
    'classname' => 'PostObjectV4',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\PostObjectV4',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\PutObjectUrlMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'PutObjectUrlMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\PutObjectUrlMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\RegionalEndpoint\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\RegionalEndpoint',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\RegionalEndpoint\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\S3\\RegionalEndpoint\\ConfigurationInterface',
    ),
  ),
  'Aws\\S3\\RegionalEndpoint\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\RegionalEndpoint\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\RegionalEndpoint\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\S3\\RetryableMalformedResponseParser' => 
  array (
    'type' => 'class',
    'classname' => 'RetryableMalformedResponseParser',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\RetryableMalformedResponseParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\S3Client' => 
  array (
    'type' => 'class',
    'classname' => 'S3Client',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\S3Client',
    'implements' => 
    array (
      0 => 'Aws\\S3\\S3ClientInterface',
    ),
  ),
  'Aws\\S3\\S3EndpointMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'S3EndpointMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\S3EndpointMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\S3MultiRegionClient' => 
  array (
    'type' => 'class',
    'classname' => 'S3MultiRegionClient',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\S3MultiRegionClient',
    'implements' => 
    array (
      0 => 'Aws\\S3\\S3ClientInterface',
    ),
  ),
  'Aws\\S3\\S3UriParser' => 
  array (
    'type' => 'class',
    'classname' => 'S3UriParser',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\S3UriParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\SSECMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'SSECMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\SSECMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\StreamWrapper' => 
  array (
    'type' => 'class',
    'classname' => 'StreamWrapper',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\StreamWrapper',
    'implements' => 
    array (
    ),
  ),
  'Aws\\S3\\Transfer' => 
  array (
    'type' => 'class',
    'classname' => 'Transfer',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\Transfer',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'Aws\\S3\\UseArnRegion\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\UseArnRegion',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\UseArnRegion\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\S3\\UseArnRegion\\ConfigurationInterface',
    ),
  ),
  'Aws\\S3\\UseArnRegion\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\S3\\UseArnRegion\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\UseArnRegion\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\S3\\ValidateResponseChecksumParser' => 
  array (
    'type' => 'class',
    'classname' => 'ValidateResponseChecksumParser',
    'isabstract' => false,
    'namespace' => 'Aws\\S3',
    'extends' => 'ClassifaiVendor\\Aws\\S3\\ValidateResponseChecksumParser',
    'implements' => 
    array (
    ),
  ),
  'Aws\\SSO\\Exception\\SSOException' => 
  array (
    'type' => 'class',
    'classname' => 'SSOException',
    'isabstract' => false,
    'namespace' => 'Aws\\SSO\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\SSO\\Exception\\SSOException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\SSO\\SSOClient' => 
  array (
    'type' => 'class',
    'classname' => 'SSOClient',
    'isabstract' => false,
    'namespace' => 'Aws\\SSO',
    'extends' => 'ClassifaiVendor\\Aws\\SSO\\SSOClient',
    'implements' => 
    array (
    ),
  ),
  'Aws\\SSOOIDC\\Exception\\SSOOIDCException' => 
  array (
    'type' => 'class',
    'classname' => 'SSOOIDCException',
    'isabstract' => false,
    'namespace' => 'Aws\\SSOOIDC\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\SSOOIDC\\Exception\\SSOOIDCException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\SSOOIDC\\SSOOIDCClient' => 
  array (
    'type' => 'class',
    'classname' => 'SSOOIDCClient',
    'isabstract' => false,
    'namespace' => 'Aws\\SSOOIDC',
    'extends' => 'ClassifaiVendor\\Aws\\SSOOIDC\\SSOOIDCClient',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Script\\Composer\\Composer' => 
  array (
    'type' => 'class',
    'classname' => 'Composer',
    'isabstract' => false,
    'namespace' => 'Aws\\Script\\Composer',
    'extends' => 'ClassifaiVendor\\Aws\\Script\\Composer\\Composer',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Sdk' => 
  array (
    'type' => 'class',
    'classname' => 'Sdk',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\Sdk',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Signature\\AnonymousSignature' => 
  array (
    'type' => 'class',
    'classname' => 'AnonymousSignature',
    'isabstract' => false,
    'namespace' => 'Aws\\Signature',
    'extends' => 'ClassifaiVendor\\Aws\\Signature\\AnonymousSignature',
    'implements' => 
    array (
      0 => 'Aws\\Signature\\SignatureInterface',
    ),
  ),
  'Aws\\Signature\\S3ExpressSignature' => 
  array (
    'type' => 'class',
    'classname' => 'S3ExpressSignature',
    'isabstract' => false,
    'namespace' => 'Aws\\Signature',
    'extends' => 'ClassifaiVendor\\Aws\\Signature\\S3ExpressSignature',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Signature\\S3SignatureV4' => 
  array (
    'type' => 'class',
    'classname' => 'S3SignatureV4',
    'isabstract' => false,
    'namespace' => 'Aws\\Signature',
    'extends' => 'ClassifaiVendor\\Aws\\Signature\\S3SignatureV4',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Signature\\SignatureV4' => 
  array (
    'type' => 'class',
    'classname' => 'SignatureV4',
    'isabstract' => false,
    'namespace' => 'Aws\\Signature',
    'extends' => 'ClassifaiVendor\\Aws\\Signature\\SignatureV4',
    'implements' => 
    array (
      0 => 'Aws\\Signature\\SignatureInterface',
    ),
  ),
  'Aws\\StreamRequestPayloadMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'StreamRequestPayloadMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\StreamRequestPayloadMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Sts\\Exception\\StsException' => 
  array (
    'type' => 'class',
    'classname' => 'StsException',
    'isabstract' => false,
    'namespace' => 'Aws\\Sts\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Sts\\Exception\\StsException',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Sts\\RegionalEndpoints\\Configuration' => 
  array (
    'type' => 'class',
    'classname' => 'Configuration',
    'isabstract' => false,
    'namespace' => 'Aws\\Sts\\RegionalEndpoints',
    'extends' => 'ClassifaiVendor\\Aws\\Sts\\RegionalEndpoints\\Configuration',
    'implements' => 
    array (
      0 => 'Aws\\Sts\\RegionalEndpoints\\ConfigurationInterface',
    ),
  ),
  'Aws\\Sts\\RegionalEndpoints\\Exception\\ConfigurationException' => 
  array (
    'type' => 'class',
    'classname' => 'ConfigurationException',
    'isabstract' => false,
    'namespace' => 'Aws\\Sts\\RegionalEndpoints\\Exception',
    'extends' => 'ClassifaiVendor\\Aws\\Sts\\RegionalEndpoints\\Exception\\ConfigurationException',
    'implements' => 
    array (
      0 => 'Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\Sts\\StsClient' => 
  array (
    'type' => 'class',
    'classname' => 'StsClient',
    'isabstract' => false,
    'namespace' => 'Aws\\Sts',
    'extends' => 'ClassifaiVendor\\Aws\\Sts\\StsClient',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Token\\BearerTokenAuthorization' => 
  array (
    'type' => 'class',
    'classname' => 'BearerTokenAuthorization',
    'isabstract' => false,
    'namespace' => 'Aws\\Token',
    'extends' => 'ClassifaiVendor\\Aws\\Token\\BearerTokenAuthorization',
    'implements' => 
    array (
      0 => 'Aws\\Token\\TokenAuthorization',
    ),
  ),
  'Aws\\Token\\SsoToken' => 
  array (
    'type' => 'class',
    'classname' => 'SsoToken',
    'isabstract' => false,
    'namespace' => 'Aws\\Token',
    'extends' => 'ClassifaiVendor\\Aws\\Token\\SsoToken',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Token\\SsoTokenProvider' => 
  array (
    'type' => 'class',
    'classname' => 'SsoTokenProvider',
    'isabstract' => false,
    'namespace' => 'Aws\\Token',
    'extends' => 'ClassifaiVendor\\Aws\\Token\\SsoTokenProvider',
    'implements' => 
    array (
      0 => 'Aws\\Token\\RefreshableTokenProviderInterface',
    ),
  ),
  'Aws\\Token\\Token' => 
  array (
    'type' => 'class',
    'classname' => 'Token',
    'isabstract' => false,
    'namespace' => 'Aws\\Token',
    'extends' => 'ClassifaiVendor\\Aws\\Token\\Token',
    'implements' => 
    array (
      0 => 'Aws\\Token\\TokenInterface',
      1 => 'Serializable',
    ),
  ),
  'Aws\\TraceMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'TraceMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\TraceMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\UserAgentMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'UserAgentMiddleware',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\UserAgentMiddleware',
    'implements' => 
    array (
    ),
  ),
  'Aws\\Waiter' => 
  array (
    'type' => 'class',
    'classname' => 'Waiter',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\Waiter',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'Aws\\WrappedHttpHandler' => 
  array (
    'type' => 'class',
    'classname' => 'WrappedHttpHandler',
    'isabstract' => false,
    'namespace' => 'Aws',
    'extends' => 'ClassifaiVendor\\Aws\\WrappedHttpHandler',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\BodySummarizer' => 
  array (
    'type' => 'class',
    'classname' => 'BodySummarizer',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\BodySummarizer',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\BodySummarizerInterface',
    ),
  ),
  'GuzzleHttp\\Client' => 
  array (
    'type' => 'class',
    'classname' => 'Client',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Client',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\ClientInterface',
      1 => 'Psr\\Http\\Client\\ClientInterface',
    ),
  ),
  'GuzzleHttp\\Cookie\\CookieJar' => 
  array (
    'type' => 'class',
    'classname' => 'CookieJar',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Cookie',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Cookie\\CookieJar',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Cookie\\CookieJarInterface',
    ),
  ),
  'GuzzleHttp\\Cookie\\FileCookieJar' => 
  array (
    'type' => 'class',
    'classname' => 'FileCookieJar',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Cookie',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Cookie\\FileCookieJar',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Cookie\\SessionCookieJar' => 
  array (
    'type' => 'class',
    'classname' => 'SessionCookieJar',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Cookie',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Cookie\\SessionCookieJar',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Cookie\\SetCookie' => 
  array (
    'type' => 'class',
    'classname' => 'SetCookie',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Cookie',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Cookie\\SetCookie',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Exception\\BadResponseException' => 
  array (
    'type' => 'class',
    'classname' => 'BadResponseException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\BadResponseException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Exception\\ClientException' => 
  array (
    'type' => 'class',
    'classname' => 'ClientException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\ClientException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Exception\\ConnectException' => 
  array (
    'type' => 'class',
    'classname' => 'ConnectException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\ConnectException',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Client\\NetworkExceptionInterface',
    ),
  ),
  'GuzzleHttp\\Exception\\InvalidArgumentException' => 
  array (
    'type' => 'class',
    'classname' => 'InvalidArgumentException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\InvalidArgumentException',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Exception\\GuzzleException',
    ),
  ),
  'GuzzleHttp\\Exception\\RequestException' => 
  array (
    'type' => 'class',
    'classname' => 'RequestException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\RequestException',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Client\\RequestExceptionInterface',
    ),
  ),
  'GuzzleHttp\\Exception\\ServerException' => 
  array (
    'type' => 'class',
    'classname' => 'ServerException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\ServerException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Exception\\TooManyRedirectsException' => 
  array (
    'type' => 'class',
    'classname' => 'TooManyRedirectsException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\TooManyRedirectsException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Exception\\TransferException' => 
  array (
    'type' => 'class',
    'classname' => 'TransferException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Exception\\TransferException',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Exception\\GuzzleException',
    ),
  ),
  'GuzzleHttp\\Handler\\CurlFactory' => 
  array (
    'type' => 'class',
    'classname' => 'CurlFactory',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\CurlFactory',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Handler\\CurlFactoryInterface',
    ),
  ),
  'GuzzleHttp\\Handler\\CurlHandler' => 
  array (
    'type' => 'class',
    'classname' => 'CurlHandler',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\CurlHandler',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\CurlMultiHandler' => 
  array (
    'type' => 'class',
    'classname' => 'CurlMultiHandler',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\CurlMultiHandler',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\CurlShareHandleState' => 
  array (
    'type' => 'class',
    'classname' => 'CurlShareHandleState',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\CurlShareHandleState',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\CurlVersion' => 
  array (
    'type' => 'class',
    'classname' => 'CurlVersion',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\CurlVersion',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\EasyHandle' => 
  array (
    'type' => 'class',
    'classname' => 'EasyHandle',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\EasyHandle',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\HeaderProcessor' => 
  array (
    'type' => 'class',
    'classname' => 'HeaderProcessor',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\HeaderProcessor',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\MockHandler' => 
  array (
    'type' => 'class',
    'classname' => 'MockHandler',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\MockHandler',
    'implements' => 
    array (
      0 => 'Countable',
    ),
  ),
  'GuzzleHttp\\Handler\\Proxy' => 
  array (
    'type' => 'class',
    'classname' => 'Proxy',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\Proxy',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\ProxyEnvironment' => 
  array (
    'type' => 'class',
    'classname' => 'ProxyEnvironment',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\ProxyEnvironment',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Handler\\StreamHandler' => 
  array (
    'type' => 'class',
    'classname' => 'StreamHandler',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Handler\\StreamHandler',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\HandlerStack' => 
  array (
    'type' => 'class',
    'classname' => 'HandlerStack',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\HandlerStack',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\MessageFormatter' => 
  array (
    'type' => 'class',
    'classname' => 'MessageFormatter',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\MessageFormatter',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\MessageFormatterInterface',
    ),
  ),
  'GuzzleHttp\\Middleware' => 
  array (
    'type' => 'class',
    'classname' => 'Middleware',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Middleware',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Pool' => 
  array (
    'type' => 'class',
    'classname' => 'Pool',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Pool',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'GuzzleHttp\\PrepareBodyMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'PrepareBodyMiddleware',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\PrepareBodyMiddleware',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\RedirectMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'RedirectMiddleware',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\RedirectMiddleware',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\RequestOptions' => 
  array (
    'type' => 'class',
    'classname' => 'RequestOptions',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\RequestOptions',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\RetryMiddleware' => 
  array (
    'type' => 'class',
    'classname' => 'RetryMiddleware',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\RetryMiddleware',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\TransferStats' => 
  array (
    'type' => 'class',
    'classname' => 'TransferStats',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\TransferStats',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\TransportSharing' => 
  array (
    'type' => 'class',
    'classname' => 'TransportSharing',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\TransportSharing',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Utils' => 
  array (
    'type' => 'class',
    'classname' => 'Utils',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Utils',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Promise\\AggregateException' => 
  array (
    'type' => 'class',
    'classname' => 'AggregateException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\AggregateException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Promise\\CancellationException' => 
  array (
    'type' => 'class',
    'classname' => 'CancellationException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\CancellationException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Promise\\Coroutine' => 
  array (
    'type' => 'class',
    'classname' => 'Coroutine',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\Coroutine',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromiseInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\Create' => 
  array (
    'type' => 'class',
    'classname' => 'Create',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\Create',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Promise\\Each' => 
  array (
    'type' => 'class',
    'classname' => 'Each',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\Each',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Promise\\EachPromise' => 
  array (
    'type' => 'class',
    'classname' => 'EachPromise',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\EachPromise',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\FulfilledPromise' => 
  array (
    'type' => 'class',
    'classname' => 'FulfilledPromise',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\FulfilledPromise',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromiseInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\Is' => 
  array (
    'type' => 'class',
    'classname' => 'Is',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\Is',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Promise\\Promise' => 
  array (
    'type' => 'class',
    'classname' => 'Promise',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\Promise',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromiseInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\RejectedPromise' => 
  array (
    'type' => 'class',
    'classname' => 'RejectedPromise',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\RejectedPromise',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\PromiseInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\RejectionException' => 
  array (
    'type' => 'class',
    'classname' => 'RejectionException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\RejectionException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Promise\\TaskQueue' => 
  array (
    'type' => 'class',
    'classname' => 'TaskQueue',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\TaskQueue',
    'implements' => 
    array (
      0 => 'GuzzleHttp\\Promise\\TaskQueueInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\Utils' => 
  array (
    'type' => 'class',
    'classname' => 'Utils',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Promise\\Utils',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\AppendStream' => 
  array (
    'type' => 'class',
    'classname' => 'AppendStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\AppendStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\BufferStream' => 
  array (
    'type' => 'class',
    'classname' => 'BufferStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\BufferStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\CachingStream' => 
  array (
    'type' => 'class',
    'classname' => 'CachingStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\CachingStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\DroppingStream' => 
  array (
    'type' => 'class',
    'classname' => 'DroppingStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\DroppingStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Exception\\MalformedUriException' => 
  array (
    'type' => 'class',
    'classname' => 'MalformedUriException',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7\\Exception',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Exception\\MalformedUriException',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\FnStream' => 
  array (
    'type' => 'class',
    'classname' => 'FnStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\FnStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Header' => 
  array (
    'type' => 'class',
    'classname' => 'Header',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Header',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\HttpFactory' => 
  array (
    'type' => 'class',
    'classname' => 'HttpFactory',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\HttpFactory',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\RequestFactoryInterface',
      1 => 'Psr\\Http\\Message\\ResponseFactoryInterface',
      2 => 'Psr\\Http\\Message\\ServerRequestFactoryInterface',
      3 => 'Psr\\Http\\Message\\StreamFactoryInterface',
      4 => 'Psr\\Http\\Message\\UploadedFileFactoryInterface',
      5 => 'Psr\\Http\\Message\\UriFactoryInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\InflateStream' => 
  array (
    'type' => 'class',
    'classname' => 'InflateStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\InflateStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\LazyOpenStream' => 
  array (
    'type' => 'class',
    'classname' => 'LazyOpenStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\LazyOpenStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\LimitStream' => 
  array (
    'type' => 'class',
    'classname' => 'LimitStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\LimitStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Message' => 
  array (
    'type' => 'class',
    'classname' => 'Message',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Message',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\MimeType' => 
  array (
    'type' => 'class',
    'classname' => 'MimeType',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\MimeType',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\MultipartStream' => 
  array (
    'type' => 'class',
    'classname' => 'MultipartStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\MultipartStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\NoSeekStream' => 
  array (
    'type' => 'class',
    'classname' => 'NoSeekStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\NoSeekStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\PumpStream' => 
  array (
    'type' => 'class',
    'classname' => 'PumpStream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\PumpStream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Query' => 
  array (
    'type' => 'class',
    'classname' => 'Query',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Query',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\Request' => 
  array (
    'type' => 'class',
    'classname' => 'Request',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Request',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\RequestInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Response' => 
  array (
    'type' => 'class',
    'classname' => 'Response',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Response',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\ResponseInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Rfc3986' => 
  array (
    'type' => 'class',
    'classname' => 'Rfc3986',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Rfc3986',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\Rfc7230' => 
  array (
    'type' => 'class',
    'classname' => 'Rfc7230',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Rfc7230',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\ServerRequest' => 
  array (
    'type' => 'class',
    'classname' => 'ServerRequest',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\ServerRequest',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\ServerRequestInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Stream' => 
  array (
    'type' => 'class',
    'classname' => 'Stream',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Stream',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\StreamWrapper' => 
  array (
    'type' => 'class',
    'classname' => 'StreamWrapper',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\StreamWrapper',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\UploadedFile' => 
  array (
    'type' => 'class',
    'classname' => 'UploadedFile',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\UploadedFile',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\UploadedFileInterface',
    ),
  ),
  'GuzzleHttp\\Psr7\\Uri' => 
  array (
    'type' => 'class',
    'classname' => 'Uri',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Uri',
    'implements' => 
    array (
      0 => 'Psr\\Http\\Message\\UriInterface',
      1 => 'JsonSerializable',
    ),
  ),
  'GuzzleHttp\\Psr7\\UriComparator' => 
  array (
    'type' => 'class',
    'classname' => 'UriComparator',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\UriComparator',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\UriNormalizer' => 
  array (
    'type' => 'class',
    'classname' => 'UriNormalizer',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\UriNormalizer',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\UriResolver' => 
  array (
    'type' => 'class',
    'classname' => 'UriResolver',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\UriResolver',
    'implements' => 
    array (
    ),
  ),
  'GuzzleHttp\\Psr7\\Utils' => 
  array (
    'type' => 'class',
    'classname' => 'Utils',
    'isabstract' => false,
    'namespace' => 'GuzzleHttp\\Psr7',
    'extends' => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\Utils',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\AstRuntime' => 
  array (
    'type' => 'class',
    'classname' => 'AstRuntime',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\AstRuntime',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\CompilerRuntime' => 
  array (
    'type' => 'class',
    'classname' => 'CompilerRuntime',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\CompilerRuntime',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\DebugRuntime' => 
  array (
    'type' => 'class',
    'classname' => 'DebugRuntime',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\DebugRuntime',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\Env' => 
  array (
    'type' => 'class',
    'classname' => 'Env',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\Env',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\FnDispatcher' => 
  array (
    'type' => 'class',
    'classname' => 'FnDispatcher',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\FnDispatcher',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\Lexer' => 
  array (
    'type' => 'class',
    'classname' => 'Lexer',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\Lexer',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\Parser' => 
  array (
    'type' => 'class',
    'classname' => 'Parser',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\Parser',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\SyntaxErrorException' => 
  array (
    'type' => 'class',
    'classname' => 'SyntaxErrorException',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\SyntaxErrorException',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\TreeCompiler' => 
  array (
    'type' => 'class',
    'classname' => 'TreeCompiler',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\TreeCompiler',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\TreeInterpreter' => 
  array (
    'type' => 'class',
    'classname' => 'TreeInterpreter',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\TreeInterpreter',
    'implements' => 
    array (
    ),
  ),
  'JmesPath\\Utils' => 
  array (
    'type' => 'class',
    'classname' => 'Utils',
    'isabstract' => false,
    'namespace' => 'JmesPath',
    'extends' => 'ClassifaiVendor\\JmesPath\\Utils',
    'implements' => 
    array (
    ),
  ),
  'Symfony\\Polyfill\\Mbstring\\Mbstring' => 
  array (
    'type' => 'class',
    'classname' => 'Mbstring',
    'isabstract' => false,
    'namespace' => 'Symfony\\Polyfill\\Mbstring',
    'extends' => 'ClassifaiVendor\\Symfony\\Polyfill\\Mbstring\\Mbstring',
    'implements' => 
    array (
    ),
  ),
  'Symfony\\Polyfill\\Php80\\Php80' => 
  array (
    'type' => 'class',
    'classname' => 'Php80',
    'isabstract' => false,
    'namespace' => 'Symfony\\Polyfill\\Php80',
    'extends' => 'ClassifaiVendor\\Symfony\\Polyfill\\Php80\\Php80',
    'implements' => 
    array (
    ),
  ),
  'Symfony\\Polyfill\\Php80\\PhpToken' => 
  array (
    'type' => 'class',
    'classname' => 'PhpToken',
    'isabstract' => false,
    'namespace' => 'Symfony\\Polyfill\\Php80',
    'extends' => 'ClassifaiVendor\\Symfony\\Polyfill\\Php80\\PhpToken',
    'implements' => 
    array (
      0 => 'Stringable',
    ),
  ),
  'Aws\\Api\\ErrorParser\\JsonParserTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'JsonParserTrait',
    'namespace' => 'Aws\\Api\\ErrorParser',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Api\\ErrorParser\\JsonParserTrait',
    ),
  ),
  'Aws\\Api\\Parser\\MetadataParserTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'MetadataParserTrait',
    'namespace' => 'Aws\\Api\\Parser',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Api\\Parser\\MetadataParserTrait',
    ),
  ),
  'Aws\\Api\\Parser\\PayloadParserTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'PayloadParserTrait',
    'namespace' => 'Aws\\Api\\Parser',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Api\\Parser\\PayloadParserTrait',
    ),
  ),
  'Aws\\Arn\\ResourceTypeAndIdTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'ResourceTypeAndIdTrait',
    'namespace' => 'Aws\\Arn',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Arn\\ResourceTypeAndIdTrait',
    ),
  ),
  'Aws\\AwsClientTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'AwsClientTrait',
    'namespace' => 'Aws',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\AwsClientTrait',
    ),
  ),
  'Aws\\Crypto\\Cipher\\CipherBuilderTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'CipherBuilderTrait',
    'namespace' => 'Aws\\Crypto\\Cipher',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\Cipher\\CipherBuilderTrait',
    ),
  ),
  'Aws\\Crypto\\DecryptionTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'DecryptionTrait',
    'namespace' => 'Aws\\Crypto',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\DecryptionTrait',
    ),
  ),
  'Aws\\Crypto\\DecryptionTraitV2' => 
  array (
    'type' => 'trait',
    'traitname' => 'DecryptionTraitV2',
    'namespace' => 'Aws\\Crypto',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\DecryptionTraitV2',
    ),
  ),
  'Aws\\Crypto\\EncryptionTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'EncryptionTrait',
    'namespace' => 'Aws\\Crypto',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\EncryptionTrait',
    ),
  ),
  'Aws\\Crypto\\EncryptionTraitV2' => 
  array (
    'type' => 'trait',
    'traitname' => 'EncryptionTraitV2',
    'namespace' => 'Aws\\Crypto',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\EncryptionTraitV2',
    ),
  ),
  'Aws\\Crypto\\Polyfill\\NeedsTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'NeedsTrait',
    'namespace' => 'Aws\\Crypto\\Polyfill',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\Polyfill\\NeedsTrait',
    ),
  ),
  'Aws\\EndpointV2\\EndpointV2SerializerTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'EndpointV2SerializerTrait',
    'namespace' => 'Aws\\EndpointV2',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\EndpointV2\\EndpointV2SerializerTrait',
    ),
  ),
  'Aws\\HasDataTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'HasDataTrait',
    'namespace' => 'Aws',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\HasDataTrait',
    ),
  ),
  'Aws\\HasMonitoringEventsTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'HasMonitoringEventsTrait',
    'namespace' => 'Aws',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\HasMonitoringEventsTrait',
    ),
  ),
  'Aws\\Retry\\RetryHelperTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'RetryHelperTrait',
    'namespace' => 'Aws\\Retry',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Retry\\RetryHelperTrait',
    ),
  ),
  'Aws\\S3\\CalculatesChecksumTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'CalculatesChecksumTrait',
    'namespace' => 'Aws\\S3',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\CalculatesChecksumTrait',
    ),
  ),
  'Aws\\S3\\Crypto\\CryptoParamsTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'CryptoParamsTrait',
    'namespace' => 'Aws\\S3\\Crypto',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\Crypto\\CryptoParamsTrait',
    ),
  ),
  'Aws\\S3\\Crypto\\CryptoParamsTraitV2' => 
  array (
    'type' => 'trait',
    'traitname' => 'CryptoParamsTraitV2',
    'namespace' => 'Aws\\S3\\Crypto',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\Crypto\\CryptoParamsTraitV2',
    ),
  ),
  'Aws\\S3\\Crypto\\UserAgentTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'UserAgentTrait',
    'namespace' => 'Aws\\S3\\Crypto',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\Crypto\\UserAgentTrait',
    ),
  ),
  'Aws\\S3\\EndpointRegionHelperTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'EndpointRegionHelperTrait',
    'namespace' => 'Aws\\S3',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\EndpointRegionHelperTrait',
    ),
  ),
  'Aws\\S3\\MultipartUploadingTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'MultipartUploadingTrait',
    'namespace' => 'Aws\\S3',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\MultipartUploadingTrait',
    ),
  ),
  'Aws\\S3\\S3ClientTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'S3ClientTrait',
    'namespace' => 'Aws\\S3',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\S3ClientTrait',
    ),
  ),
  'Aws\\Signature\\SignatureTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'SignatureTrait',
    'namespace' => 'Aws\\Signature',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Signature\\SignatureTrait',
    ),
  ),
  'Aws\\Token\\ParsesIniTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'ParsesIniTrait',
    'namespace' => 'Aws\\Token',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Token\\ParsesIniTrait',
    ),
  ),
  'GuzzleHttp\\ClientTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'ClientTrait',
    'namespace' => 'GuzzleHttp',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\ClientTrait',
    ),
  ),
  'GuzzleHttp\\Psr7\\MessageTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'MessageTrait',
    'namespace' => 'GuzzleHttp\\Psr7',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\MessageTrait',
    ),
  ),
  'GuzzleHttp\\Psr7\\StreamDecoratorTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'StreamDecoratorTrait',
    'namespace' => 'GuzzleHttp\\Psr7',
    'use' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Psr7\\StreamDecoratorTrait',
    ),
  ),
  'Aws\\Arn\\AccessPointArnInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'AccessPointArnInterface',
    'namespace' => 'Aws\\Arn',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Arn\\AccessPointArnInterface',
    ),
  ),
  'Aws\\Arn\\ArnInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ArnInterface',
    'namespace' => 'Aws\\Arn',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Arn\\ArnInterface',
    ),
  ),
  'Aws\\Arn\\S3\\BucketArnInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'BucketArnInterface',
    'namespace' => 'Aws\\Arn\\S3',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Arn\\S3\\BucketArnInterface',
    ),
  ),
  'Aws\\Arn\\S3\\OutpostsArnInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'OutpostsArnInterface',
    'namespace' => 'Aws\\Arn\\S3',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Arn\\S3\\OutpostsArnInterface',
    ),
  ),
  'Aws\\Auth\\AuthSchemeResolverInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'AuthSchemeResolverInterface',
    'namespace' => 'Aws\\Auth',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Auth\\AuthSchemeResolverInterface',
    ),
  ),
  'Aws\\AwsClientInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'AwsClientInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\AwsClientInterface',
    ),
  ),
  'Aws\\CacheInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'CacheInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\CacheInterface',
    ),
  ),
  'Aws\\ClientSideMonitoring\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\ClientSideMonitoring',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\ClientSideMonitoring\\ConfigurationInterface',
    ),
  ),
  'Aws\\ClientSideMonitoring\\MonitoringMiddlewareInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'MonitoringMiddlewareInterface',
    'namespace' => 'Aws\\ClientSideMonitoring',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\ClientSideMonitoring\\MonitoringMiddlewareInterface',
    ),
  ),
  'Aws\\CommandInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'CommandInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\CommandInterface',
    ),
  ),
  'Aws\\ConfigurationProviderInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationProviderInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\ConfigurationProviderInterface',
    ),
  ),
  'Aws\\Credentials\\CredentialsInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'CredentialsInterface',
    'namespace' => 'Aws\\Credentials',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Credentials\\CredentialsInterface',
    ),
  ),
  'Aws\\Crypto\\AesStreamInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'AesStreamInterface',
    'namespace' => 'Aws\\Crypto',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\AesStreamInterface',
    ),
  ),
  'Aws\\Crypto\\AesStreamInterfaceV2' => 
  array (
    'type' => 'interface',
    'interfacename' => 'AesStreamInterfaceV2',
    'namespace' => 'Aws\\Crypto',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\AesStreamInterfaceV2',
    ),
  ),
  'Aws\\Crypto\\Cipher\\CipherMethod' => 
  array (
    'type' => 'interface',
    'interfacename' => 'CipherMethod',
    'namespace' => 'Aws\\Crypto\\Cipher',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\Cipher\\CipherMethod',
    ),
  ),
  'Aws\\Crypto\\MaterialsProviderInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'MaterialsProviderInterface',
    'namespace' => 'Aws\\Crypto',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\MaterialsProviderInterface',
    ),
  ),
  'Aws\\Crypto\\MaterialsProviderInterfaceV2' => 
  array (
    'type' => 'interface',
    'interfacename' => 'MaterialsProviderInterfaceV2',
    'namespace' => 'Aws\\Crypto',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\MaterialsProviderInterfaceV2',
    ),
  ),
  'Aws\\Crypto\\MetadataStrategyInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'MetadataStrategyInterface',
    'namespace' => 'Aws\\Crypto',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Crypto\\MetadataStrategyInterface',
    ),
  ),
  'Aws\\DefaultsMode\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\DefaultsMode',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\DefaultsMode\\ConfigurationInterface',
    ),
  ),
  'Aws\\Endpoint\\PartitionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'PartitionInterface',
    'namespace' => 'Aws\\Endpoint',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Endpoint\\PartitionInterface',
    ),
  ),
  'Aws\\Endpoint\\UseDualstackEndpoint\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\Endpoint\\UseDualstackEndpoint',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Endpoint\\UseDualstackEndpoint\\ConfigurationInterface',
    ),
  ),
  'Aws\\Endpoint\\UseFipsEndpoint\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\Endpoint\\UseFipsEndpoint',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Endpoint\\UseFipsEndpoint\\ConfigurationInterface',
    ),
  ),
  'Aws\\EndpointDiscovery\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\EndpointDiscovery',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\EndpointDiscovery\\ConfigurationInterface',
    ),
  ),
  'Aws\\HashInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'HashInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\HashInterface',
    ),
  ),
  'Aws\\Identity\\IdentityInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'IdentityInterface',
    'namespace' => 'Aws\\Identity',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Identity\\IdentityInterface',
    ),
  ),
  'Aws\\MonitoringEventsInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'MonitoringEventsInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\MonitoringEventsInterface',
    ),
  ),
  'Aws\\ResponseContainerInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ResponseContainerInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\ResponseContainerInterface',
    ),
  ),
  'Aws\\ResultInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ResultInterface',
    'namespace' => 'Aws',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\ResultInterface',
    ),
  ),
  'Aws\\Retry\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\Retry',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Retry\\ConfigurationInterface',
    ),
  ),
  'Aws\\S3\\Parser\\S3ResultMutator' => 
  array (
    'type' => 'interface',
    'interfacename' => 'S3ResultMutator',
    'namespace' => 'Aws\\S3\\Parser',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\Parser\\S3ResultMutator',
    ),
  ),
  'Aws\\S3\\RegionalEndpoint\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\S3\\RegionalEndpoint',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\RegionalEndpoint\\ConfigurationInterface',
    ),
  ),
  'Aws\\S3\\S3ClientInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'S3ClientInterface',
    'namespace' => 'Aws\\S3',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\S3ClientInterface',
    ),
  ),
  'Aws\\S3\\UseArnRegion\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\S3\\UseArnRegion',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\S3\\UseArnRegion\\ConfigurationInterface',
    ),
  ),
  'Aws\\Signature\\SignatureInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'SignatureInterface',
    'namespace' => 'Aws\\Signature',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Signature\\SignatureInterface',
    ),
  ),
  'Aws\\Sts\\RegionalEndpoints\\ConfigurationInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ConfigurationInterface',
    'namespace' => 'Aws\\Sts\\RegionalEndpoints',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Sts\\RegionalEndpoints\\ConfigurationInterface',
    ),
  ),
  'Aws\\Token\\RefreshableTokenProviderInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'RefreshableTokenProviderInterface',
    'namespace' => 'Aws\\Token',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Token\\RefreshableTokenProviderInterface',
    ),
  ),
  'Aws\\Token\\TokenAuthorization' => 
  array (
    'type' => 'interface',
    'interfacename' => 'TokenAuthorization',
    'namespace' => 'Aws\\Token',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Token\\TokenAuthorization',
    ),
  ),
  'Aws\\Token\\TokenInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'TokenInterface',
    'namespace' => 'Aws\\Token',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Aws\\Token\\TokenInterface',
    ),
  ),
  'GuzzleHttp\\BodySummarizerInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'BodySummarizerInterface',
    'namespace' => 'GuzzleHttp',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\BodySummarizerInterface',
    ),
  ),
  'GuzzleHttp\\ClientInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ClientInterface',
    'namespace' => 'GuzzleHttp',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\ClientInterface',
    ),
  ),
  'GuzzleHttp\\Cookie\\CookieJarInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'CookieJarInterface',
    'namespace' => 'GuzzleHttp\\Cookie',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Cookie\\CookieJarInterface',
    ),
  ),
  'GuzzleHttp\\Exception\\GuzzleException' => 
  array (
    'type' => 'interface',
    'interfacename' => 'GuzzleException',
    'namespace' => 'GuzzleHttp\\Exception',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Exception\\GuzzleException',
    ),
  ),
  'GuzzleHttp\\Handler\\CurlFactoryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'CurlFactoryInterface',
    'namespace' => 'GuzzleHttp\\Handler',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Handler\\CurlFactoryInterface',
    ),
  ),
  'GuzzleHttp\\MessageFormatterInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'MessageFormatterInterface',
    'namespace' => 'GuzzleHttp',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\MessageFormatterInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\PromiseInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'PromiseInterface',
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Promise\\PromiseInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\PromisorInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'PromisorInterface',
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Promise\\PromisorInterface',
    ),
  ),
  'GuzzleHttp\\Promise\\TaskQueueInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'TaskQueueInterface',
    'namespace' => 'GuzzleHttp\\Promise',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\GuzzleHttp\\Promise\\TaskQueueInterface',
    ),
  ),
  'Psr\\Http\\Client\\ClientExceptionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ClientExceptionInterface',
    'namespace' => 'Psr\\Http\\Client',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Client\\ClientExceptionInterface',
    ),
  ),
  'Psr\\Http\\Client\\ClientInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ClientInterface',
    'namespace' => 'Psr\\Http\\Client',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Client\\ClientInterface',
    ),
  ),
  'Psr\\Http\\Client\\NetworkExceptionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'NetworkExceptionInterface',
    'namespace' => 'Psr\\Http\\Client',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Client\\NetworkExceptionInterface',
    ),
  ),
  'Psr\\Http\\Client\\RequestExceptionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'RequestExceptionInterface',
    'namespace' => 'Psr\\Http\\Client',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Client\\RequestExceptionInterface',
    ),
  ),
  'Psr\\Http\\Message\\RequestFactoryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'RequestFactoryInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\RequestFactoryInterface',
    ),
  ),
  'Psr\\Http\\Message\\ResponseFactoryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ResponseFactoryInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\ResponseFactoryInterface',
    ),
  ),
  'Psr\\Http\\Message\\ServerRequestFactoryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ServerRequestFactoryInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\ServerRequestFactoryInterface',
    ),
  ),
  'Psr\\Http\\Message\\StreamFactoryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'StreamFactoryInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\StreamFactoryInterface',
    ),
  ),
  'Psr\\Http\\Message\\UploadedFileFactoryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'UploadedFileFactoryInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\UploadedFileFactoryInterface',
    ),
  ),
  'Psr\\Http\\Message\\UriFactoryInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'UriFactoryInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\UriFactoryInterface',
    ),
  ),
  'Psr\\Http\\Message\\MessageInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'MessageInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\MessageInterface',
    ),
  ),
  'Psr\\Http\\Message\\RequestInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'RequestInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\RequestInterface',
    ),
  ),
  'Psr\\Http\\Message\\ResponseInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ResponseInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\ResponseInterface',
    ),
  ),
  'Psr\\Http\\Message\\ServerRequestInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ServerRequestInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\ServerRequestInterface',
    ),
  ),
  'Psr\\Http\\Message\\StreamInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'StreamInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\StreamInterface',
    ),
  ),
  'Psr\\Http\\Message\\UploadedFileInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'UploadedFileInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\UploadedFileInterface',
    ),
  ),
  'Psr\\Http\\Message\\UriInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'UriInterface',
    'namespace' => 'Psr\\Http\\Message',
    'extends' => 
    array (
      0 => 'ClassifaiVendor\\Psr\\Http\\Message\\UriInterface',
    ),
  ),
  'Stringable' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Stringable',
    'namespace' => '\\',
    'extends' => 
    array (
      0 => 'ClassifaiVendor_Stringable',
    ),
  ),
);

        public function __construct()
        {
            $this->includeFilePath = __DIR__ . '/autoload_alias.php';
        }

        /**
         * @param string $class
         */
        public function autoload($class): void
        {
            if (!isset($this->autoloadAliases[$class])) {
                return;
            }
            switch ($this->autoloadAliases[$class]['type']) {
                case 'class':
                        $this->load(
                            $this->classTemplate(
                                $this->autoloadAliases[$class]
                            )
                        );
                    break;
                case 'interface':
                    $this->load(
                        $this->interfaceTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'trait':
                    $this->load(
                        $this->traitTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                default:
                    // Never.
                    break;
            }
        }

        private function load(string $includeFile): void
        {
            file_put_contents($this->includeFilePath, $includeFile);
            include $this->includeFilePath;
            file_exists($this->includeFilePath) && unlink($this->includeFilePath);
        }

        /**
         * @param ClassAliasArray $class
         */
        private function classTemplate(array $class): string
        {
            $abstract = $class['isabstract'] ? 'abstract ' : '';
            $classname = $class['classname'];
            if (isset($class['namespace'])) {
                $namespace = "namespace {$class['namespace']};";
                $extends = '\\' . $class['extends'];
                $implements = empty($class['implements']) ? ''
                : ' implements \\' . implode(', \\', $class['implements']);
            } else {
                $namespace = '';
                $extends = $class['extends'];
                $implements = !empty($class['implements']) ? ''
                : ' implements ' . implode(', ', $class['implements']);
            }
            return <<<EOD
                <?php
                $namespace
                $abstract class $classname extends $extends $implements {}
                EOD;
        }

        /**
         * @param InterfaceAliasArray $interface
         */
        private function interfaceTemplate(array $interface): string
        {
            $interfacename = $interface['interfacename'];
            $namespace = isset($interface['namespace'])
            ? "namespace {$interface['namespace']};" : '';
            $extends = isset($interface['namespace'])
            ? '\\' . implode('\\ ,', $interface['extends'])
            : implode(', ', $interface['extends']);
            return <<<EOD
                <?php
                $namespace
                interface $interfacename extends $extends {}
                EOD;
        }

        /**
         * @param TraitAliasArray $trait
         */
        private function traitTemplate(array $trait): string
        {
            $traitname = $trait['traitname'];
            $namespace = isset($trait['namespace'])
            ? "namespace {$trait['namespace']};" : '';
            $uses = isset($trait['namespace'])
            ? '\\' . implode(';' . PHP_EOL . '    use \\', $trait['use'])
            : implode(';' . PHP_EOL . '    use ', $trait['use']);
            return <<<EOD
                <?php
                $namespace
                trait $traitname { 
                    use $uses; 
                }
                EOD;
        }
    }

    spl_autoload_register([ new AliasAutoloader(), 'autoload' ]);
}

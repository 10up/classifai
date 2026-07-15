<?php
namespace ClassifaiVendor\Aws\Polly;

use ClassifaiVendor\Aws\Api\Serializer\JsonBody;
use ClassifaiVendor\Aws\AwsClient;
use ClassifaiVendor\Aws\Signature\SignatureV4;
use ClassifaiVendor\GuzzleHttp\Psr7\Request;
use ClassifaiVendor\GuzzleHttp\Psr7\Uri;
use ClassifaiVendor\GuzzleHttp\Psr7;

/**
 * This client is used to interact with the **Amazon Polly** service.
 * @method \ClassifaiVendor\Aws\Result deleteLexicon(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteLexiconAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result describeVoices(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise describeVoicesAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getLexicon(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getLexiconAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getSpeechSynthesisTask(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getSpeechSynthesisTaskAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listLexicons(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listLexiconsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listSpeechSynthesisTasks(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listSpeechSynthesisTasksAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putLexicon(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putLexiconAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result startSpeechSynthesisTask(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise startSpeechSynthesisTaskAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result synthesizeSpeech(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise synthesizeSpeechAsync(array $args = [])
 */
class PollyClient extends AwsClient
{
    /** @var JsonBody */
    private $formatter;

    /**
     * Create a pre-signed URL for Polly operation `SynthesizeSpeech`
     *
     * @param array $args parameters array for `SynthesizeSpeech`
     *                    More information @see Aws\Polly\PollyClient::SynthesizeSpeech
     *
     * @return string
     */
    public function createSynthesizeSpeechPreSignedUrl(array $args)
    {
        $uri = new Uri($this->getEndpoint());
        $uri = $uri->withPath('/v1/speech');

        // Formatting parameters follows rest-json protocol
        $this->formatter = $this->formatter ?: new JsonBody($this->getApi());
        $queryArray = json_decode(
            $this->formatter->build(
                $this->getApi()->getOperation('SynthesizeSpeech')->getInput(),
                $args
            ),
            true
        );

        // Mocking a 'GET' request in pre-signing the Url
        $query = Psr7\Query::build($queryArray);
        $uri = $uri->withQuery($query);

        $request = new Request('GET', $uri);
        $request = $request->withBody(Psr7\Utils::streamFor(''));
        $signer = new SignatureV4('polly', $this->getRegion());
        return (string) $signer->presign(
            $request,
            $this->getCredentials()->wait(),
            '+15 minutes'
        )->getUri();
    }
}

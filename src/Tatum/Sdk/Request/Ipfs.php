<?php
namespace Tatum\Sdk\Request;
use Tatum\Sdk\Request;
use Tatum\Sdk\Payload;

!class_exists('\Tatum\Sdk') && exit();

/**
 * Request:Ipfs
 * 
 * @see       https://github.com/markjivko/tatum-php-sdk
 * @copyright (c) 2022 Tatum.io
 * @author    Mark Jivko, https://markjivko.com
 * @license   Apache 2.0 License, http://www.apache.org/licenses/
 */
class Ipfs extends Request {
    
    // IPFS keys
    const IPFS_META_JSON_ERC_IMAGE = 'image';
    const IPFS_META_JSON_ERC_NAME  = 'name';
    const IPFS_META_JSON_ERC_DESC  = 'description';
    const IPFS_META_JSON_ERC_PROP  = 'properties';
    
    // Response keys
    const RESPONSE_IPFS_HASH = 'ipfsHash';
    
    /**
     * Get file content from the IPFS
     * 
     * @tatum-credit 1
     * @param \Tatum\Sdk\Payload\Ipfs\Get $payload Payload
     * @throws \Exception
     * @return string IPFS file contents
     */
    public function get(Payload\Ipfs\Get $payload) {
        return $this->_run($payload);
    }
    
    /**
     * Store a file to the IPFS
     * 
     * @tatum-credit 2
     * @param \Tatum\Sdk\Payload\Ipfs\Store $payload Payload
     * @throws \Exception
     * @return string IPFS file id
     */
    public function store(Payload\Ipfs\Store $payload) {
        return $this->_run($payload);
    }
    
    /**
     * Store a file and its ERC-1155 metadata to the IPFS
     * 
     * @tatum-credit 4
     * @param \Tatum\Sdk\Payload\Ipfs\Nft $payload Payload
     * @throws \Exception
     * @return string IPFS <b>metadata</b> file id
     */
    public function nft(Payload\Ipfs\Nft $payload) {
        if (!$payload instanceof Payload\Ipfs\Nft) {
            throw new Exception('Invalid payload');
        }
        
        // Upload the asset to IPFS
        $responseAsset = $this->store(
            new Payload\Ipfs\Store(
                $payload->getFilePath()
            )
        );

        // Prepare ERC-721 and ERC-1155 metadata
        $assetMetadata = array(
            self::IPFS_META_JSON_ERC_NAME  => $payload->getErcName(),
            self::IPFS_META_JSON_ERC_DESC  => $payload->getErcDescription(),
            self::IPFS_META_JSON_ERC_IMAGE => "ipfs://" . rawurldecode(
                $responseAsset[self::RESPONSE_IPFS_HASH]
            )
        );
        
        // Optional ERC-1155 properties
        if (null !== $payload->getErcProperties()) {
            $assetMetadata[self::IPFS_META_JSON_ERC_PROP] = $payload->getErcProperties();
        }
        
        // Store metadata JSON file in a temporary location
        file_put_contents(
            $assetFilePath = stream_get_meta_data(tmpfile())['uri'], 
            json_encode(
                $assetMetadata, 
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
        
        // Upload the metadata file to IPFS
        $responseMetadata = $this->store(
            new Payload\Ipfs\Store(
                $assetFilePath
            )
        );
        
        // Get the file id for the metadata JSON file
        return rawurldecode(
            $responseMetadata[self::RESPONSE_IPFS_HASH]
        );
    }
}

/* EOF */
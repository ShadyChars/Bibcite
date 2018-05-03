<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

/**
 * A static class used to download URLs and return their values.
 * 
 * Where possible, recently-downloaded URLs may be skipped in favour of cached
 * values.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/OrkneyDullard/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class Downloader
{
    // How long should we wait between HTTP requests (= 5 mins)?
    private const HTTP_DORMANCY_SECONDS = 300;
    
    // How long should our transients live (= 30 days)?
    private const TRANSIENT_EXPIRATION_SECONDS = 3600*24*30;

    // Prefix for transients generated by this class.
    private const TRANSIENT_PREFIX = __CLASS__;
    
    /**
     * Retrieve a specified URL.
     *
     * @param string $url URL to retrieve
     * @param boolean $force_download should we always retrieve the complete 
     * file? If false, a cached or otherwise unchanged version may be used.
     * @return string contents of the requested URL
     * 
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function get_url(
        string $url, $force_download = false
    ) : string {

        // Include the target URL in our scoped logging messages.
        $logger = new ScopedLogger(
            Logger::instance(), __METHOD__ ." - $url - "
        );

        // Include the filename hash in our DB prefixes to scope our data
        $slugify = new \Cocur\Slugify\Slugify();
        $transient_prefix_for_url = $slugify->slugify(
            self::TRANSIENT_PREFIX . "_" . md5($url) . "_"
        );

        // Compute the name of the cached URL.
        $request_body_transient_name = 
            $transient_prefix_for_url . "request-body";

        // Get the last known ETag for this file, if any
        $etag_transient_name = $transient_prefix_for_url . "last-etag";
        $last_etag = 
            Transients::instance()->get_transient($etag_transient_name);
        $logger->info("Last URL ETag: " . var_export($last_etag, true));

        // Get the last known download time for this file, if any
        $last_downloaded_time_transient_name = 
            $transient_prefix_for_url . "last-downloaded-time";
        $last_downloaded_time = Transients::instance()->get_transient(
            $last_downloaded_time_transient_name
        );
        $logger->info(
            "URL last downloaded: " . var_export($last_downloaded_time, true)
        );

        // If we know when we last downloaded this file, we may be able to skip 
        // a repeated download and return the cached value.
        if ($last_downloaded_time !== false) {
            if ($force_download) {
                $logger->debug("Forcing a new download.");
            } else if (
                $last_downloaded_time >= (time() - self::HTTP_DORMANCY_SECONDS)
            ) {
                $logger->debug(
                    "URL last downloaded within " .
                    self::HTTP_DORMANCY_SECONDS .
                    " seconds. Returning cached request body."
                );
                return Transients::instance()->get_transient(
                    $request_body_transient_name
                );
            }
        }

        $logger->info("Getting URL...");

        // Get the resource, but only if it has an ETag different from the last 
        // one we saw.
        $headers = [];
        $body = [];
        $curl_error = null;
        $start_get_time = time();
        try {

            // Write to file; follow redirection; and ignore SSL certs.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            // Capture headers.
            curl_setopt(
                $ch,
                CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$headers) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) {
                        // ignore invalid headers
                        return $len;
                    }

                    $name = strtolower(trim($header[0]));
                    $value = trim($header[1]);
                    if (!array_key_exists($name, $headers)) {
                        $headers[$name] = [$value];
                    } else {
                        $headers[$name][] = $value;
                    }

                    return $len;
                }
            );

            // Only get the body of the resource if its ETag is different or if 
            // we're forcing a download.
            if (!$force_download && isset($last_etag)) {
                $logger->debug("Using ETag in request: $last_etag");
                curl_setopt(
                    $ch, 
                    CURLOPT_HTTPHEADER, 
                    array("If-None-Match: $last_etag")
                );
            }

            // Execute the request, saving the body and any errors.
            $body = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

        } catch (Exception $e) {
            $logger->error(
                "Caught exception when retrieving URL: " . $e->getMessage() . 
                ". Returning cached value."
            );
            return Transients::instance()->get_transient(
                $request_body_transient_name
            );
        }

        // If there's an error, report as much info as possible and quit now.
        if ($curl_error) {
            $logger->error(
                "Error retrieving URL: ${curl_error}. Returning cached value."
            );
            return Transients::instance()->get_transient(
                $request_body_transient_name
            );
        }

        // The request succeeded. Update our ETag and our last GET datetime.
        $new_etag = isset($headers["etag"]) ? $headers["etag"][0] : null;
        Transients::instance()->set_transient(
            $etag_transient_name,
            $new_etag,
            self::TRANSIENT_EXPIRATION_SECONDS
        );
        
        $new_download_time = time();
        Transients::instance()->set_transient(
            $last_downloaded_time_transient_name,
            $new_download_time,
            self::TRANSIENT_EXPIRATION_SECONDS
        );

        $logger->debug(
            "Writing Etag ($new_etag) and download time ($new_download_time)."
        );

        // Did we get a new copy of the resource? If not, nothing more to do.
        if (sizeof($body) == 0) {
            $logger->debug(
                "Downloaded body has not changed. Returning cached value."
            );
            return Transients::instance()->get_transient(
                $request_body_transient_name
            );
        }

        // Done. Record and return the new value of the body.
        Transients::instance()->set_transient(
            $request_body_transient_name, 
            $body, 
            self::TRANSIENT_EXPIRATION_SECONDS
        );

        return $body;
    }
}
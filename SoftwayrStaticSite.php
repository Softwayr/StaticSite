<?php

namespace Softwayr\StaticSite;

class SoftwayrStaticSite
{
    private $path_to_input_directory;
    private $path_to_output_directory;
    private $ignores;
    private $friendly_urls;
    private $data_delimiter;

    public function __construct( string $path_to_input_directory, string $path_to_output_directory, array $ignores = array(), $friendly_urls = false, $data_delimiter = '---' )
    {
        echo "\n\nWelcome to Softwayr Static Site!\n\n";

        $path_to_input_directory = trim( $path_to_input_directory );
        $path_to_output_directory = trim( $path_to_output_directory );

        $this->path_to_input_directory = $path_to_input_directory;
        $this->path_to_output_directory = $path_to_output_directory;
        $this->ignores = $ignores;
        $this->friendly_urls = $friendly_urls;
        $this->data_delimiter = $data_delimiter;

        if( $path_to_input_directory == "" || $path_to_output_directory == "" )
            die( "Input and/or Output directory path is blank!" );
        if( ! is_dir( $path_to_input_directory ) )
            die( "Input directory path is not a directory!" );
        if( ! is_readable( $path_to_input_directory ) )
            die( "Input directory path is not readable!" );
        if( is_dir( $path_to_output_directory ) && count( scandir( $path_to_output_directory ) ) > 2 )
            $this->clearDirectory( $path_to_output_directory );

        $this->processDirectoryItems( $path_to_input_directory, $path_to_output_directory );

        echo "\n\nThank you for using Softwayr Static Site!\n\n";
    }

    private function clearDirectory( string $path_to_directory, $remove_dir = false )
    {
        echo "Clearing Directory: " . $path_to_directory . "...\n\n";

        if( ! is_writable( $path_to_directory ) )
            die( "- Directory not writable: " . $path_to_directory . "\n" );

        $directoryListing = scandir( $path_to_directory );

        if( count( $directoryListing ) == 2 )
            echo "- Directory already empty! \n";

        foreach( $directoryListing as $directoryItem )
        {
            if( $directoryItem == "." || $directoryItem == ".." )
                continue;

            $directoryItemPath = $path_to_directory . DIRECTORY_SEPARATOR . $directoryItem;

            if( is_file( $directoryItemPath ) )
            {
                echo "- Deleting file: " . $directoryItemPath . "\n";
                unlink( $directoryItemPath );
                continue;
            }

            if( is_dir( $directoryItemPath ) )
            {
                $this->clearDirectory( $directoryItemPath, true );
                continue;
            }
        }
        
        if( count( $directoryListing ) > 2 )
            echo "\n...Directory Cleared!\n";

        if( $remove_dir )
        {
            echo "Removing directory: " . $path_to_directory . "...\n";
            rmdir( $path_to_directory );
            echo "...Removed!\n";
        }
    }

    private function processDirectoryItems( string $path_to_input_directory, string $path_to_output_directory )
    {
        echo "Processing items in directory: " . $path_to_input_directory . "...\n\n";
        
        if( ! is_dir( $path_to_input_directory ) )
        {
            echo "- Not A Directory!\n";
            return;
        }

        if( ! is_readable( $path_to_input_directory ) )
        {
            echo "- Not Readable!\n";
            return;
        }

        $inputDirectoryListing = scandir( $path_to_input_directory );

        if( count( $inputDirectoryListing ) == 2 )
        {
            echo "- Empty - Nothing To Process!\n";
            return;
        }

        if( ! is_dir( $path_to_output_directory ) )
        {
            mkdir( $path_to_output_directory );
            echo "- Output Directory Created: " . $path_to_output_directory;
        }

        foreach( $inputDirectoryListing as $inputDirectoryItem )
        {
            if( $inputDirectoryItem == "." || $inputDirectoryItem == ".." )
                continue;

            $inputDirectoryItemPath = $path_to_input_directory . DIRECTORY_SEPARATOR . $inputDirectoryItem;
            $outputDirectoryItemPath = $path_to_output_directory . DIRECTORY_SEPARATOR . $inputDirectoryItem;

            if( count( $this->ignores ) )
            {
                foreach( $this->ignores as $ignore )
                {
                    $ignore = trim( $ignore );

                    if( $ignore && strpos( $inputDirectoryItem, $ignore ) !== false )
                    {
                        echo "- Ignored Directory Item: " . $inputDirectoryItemPath . "\n";
                        echo "-- Because item matches ignore item: " . $ignore . "\n";
                        continue( 2 );
                    }
                }
            }

            if( is_dir( $inputDirectoryItemPath ) )
            {
                $this->processDirectoryItems( $inputDirectoryItemPath, $outputDirectoryItemPath );
                continue;
            }

            if( is_file( $inputDirectoryItemPath ) && substr( $inputDirectoryItem, -4 ) == ".php" )
            {
                $outputDirectoryItemPath = substr( $outputDirectoryItemPath, 0, -4 ) . ".html";

                $this->processPhpFile( $inputDirectoryItemPath, $outputDirectoryItemPath );
                continue;
            }

            if( is_file( $inputDirectoryItemPath ) && substr( $inputDirectoryItemPath, -5 ) == ".html" )
            {
                $this->processHtmlFile( $inputDirectoryItemPath, $outputDirectoryItemPath );
                continue;
            }

            echo "- Copying directory item: " . $inputDirectoryItemPath . " to " . $outputDirectoryItemPath . "\n";
            copy( $inputDirectoryItemPath, $outputDirectoryItemPath );
        }

        echo "Directory processing completed: " . $path_to_input_directory . "\n";
    }

    private function processPhpFile( string $inputFilePath, string $outputFilePath )
    {
        echo "- Processing PHP File: " . $inputFilePath . "\n";

        if( ! is_file( $inputFilePath ) )
        {
            echo "-- Not a File!";
            return;
        }

        ob_start();

        include $inputFilePath;
        $fileContents = ob_get_contents();

        ob_end_clean();

        $softwayrData = array();

        $fileContents = $this->processSoftwayrData( $fileContents, $softwayrData, $this->data_delimiter );

        $outputFilePath = $this->processOutputPath( $outputFilePath, $softwayrData );

        $this->outputFile( $outputFilePath, $fileContents );
    }

    private function processHtmlFile( string $inputFilePath, string $outputFilePath )
    {
        echo "- Processing HTML File: " . $inputFilePath . "\n";

        if( ! is_file( $inputFilePath ) )
        {
            echo "-- Not a File!";
            return;
        }

        $fileContents = file_get_contents( $inputFilePath );
        
        $softwayrData = array();

        $fileContents = $this->processSoftwayrData( $fileContents, $softwayrData, $this->data_delimiter );

        $outputFilePath = $this->processOutputPath( $outputFilePath, $softwayrData );

        $this->outputFile( $outputFilePath, $fileContents );
    }

    private function processSoftwayrData( string $content, array &$softwayrData, string $delimiter )
    {
        $contentLines = explode( PHP_EOL, $content );

        if( count( $contentLines ) > 0 && trim( $contentLines[ 0 ] == $delimiter ) )
        {
            echo "-- Processing Softwayr Data...\n";

            unset( $contentLines[ 0 ] );

            for( $contentLineNumber = 1; $contentLineNumber < count( $contentLines ); $contentLineNumber++ )
            {
                $contentLine = trim( $contentLines[ $contentLineNumber ] );

                unset( $contentLines[ $contentLineNumber ] );

                if( $contentLine == $delimiter )
                    break;

                if( ! strpos( $contentLine, ":" ) )
                    continue;

                $contentLine = explode( ":", $contentLine, 2 );

                $softwayrDataKey = trim( $contentLine[ 0 ] );
                $softwayrDataValue = trim( $contentLine[ 1 ] );

                echo "--- Setting Softwayr Data Key: " . $softwayrDataKey . "\n";
                echo "---- with matching value: " . $softwayrDataValue . "\n";

                $softwayrData[ $softwayrDataKey ] = $softwayrDataValue;
            }

            $content = join( PHP_EOL, $contentLines );
            
            echo "-- End of Softwayr Data!\n";
        }

        return $content;
    }

    private function processOutputPath( string $outputFilePath, array $softwayrData = array() )
    {
        if( basename( $outputFilePath ) == "index.html" )
            return $outputFilePath;

        $friendly_urls = $this->friendly_urls;

        if( isset( $softwayrData[ 'friendly_urls' ] ) )
        {
            if( $softwayrData[ 'friendly_urls' ] == "true" )
                $friendly_urls = true;
            if( $softwayrData[ 'friendly_urls' ] == "false" )
                $friendly_urls = false;
        }

        if( $friendly_urls )
        {
            $outputFilePath = substr( $outputFilePath, 0, -5 );

            if( ! is_dir( $outputFilePath ) )
            {
                mkdir( $outputFilePath );
            }

            $outputFilePath = $outputFilePath . DIRECTORY_SEPARATOR . "index.html";
        }

        return $outputFilePath;
    }

    private function outputFile( string $filePath, string $fileContents )
    {
        echo "- Outputting File: " . $filePath . "\n";

        @chmod( $filePath, 0755 );
        $opened_file = fopen( $filePath, "w" );
        fputs( $opened_file, $fileContents, strlen( $fileContents ) );
        fclose( $opened_file );
    }
}
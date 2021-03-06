<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Support\Markdown\Parser\Analysers;

use Commune\Support\Markdown\Data\MDSectionData;
use Commune\Support\Markdown\MarkdownUtils;
use Commune\Support\Markdown\Parser\MDAnalyser;
use Commune\Support\Markdown\Parser\MDParser;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class MDCommentAls extends MDAnalyser
{

    protected $methodComments = [
    ];


    public function __invoke(int $index, string $line): ? int
    {
        $commentInfo = MarkdownUtils::parseCommentLine($line);
        if (empty($commentInfo)) {
            return null;
        }
        $parser = $this->parser;

        // 继续看上一行.
        $lastMode = $parser->getLineMode($index - 1);

        // 合法, 是一个标记.
        if (
            $lastMode === MDParser::LINE_EMPTY
            // 用这种方法就不用递归了.
            || $lastMode === MDParser::LINE_COMMENT
        ) {
            list($comment, $content) = $commentInfo;
            return $this->addComment($comment, $content);
        }

        return null;
    }

    protected function addComment(string $comment, string $content) : int
    {
        if (in_array($comment, $this->parser->archiveComments)) {
            $this->parser
                ->currentSection
                ->appendComment($comment, $content);

        } elseif ($comment === MDSectionData::BLOCK_SEPARATOR) {

            $this->parser
                ->currentSection
                ->appendBlock();

        } elseif (array_key_exists($comment, $this->methodComments)) {

            $method = $this->methodComments[$comment];
            return $this->{$method} ($content);

        } else {

            $this->appendCommentLine($comment, $content);

        }

        return MDParser::LINE_COMMENT;
    }

    protected function appendCommentLine(string $comment, string $content) : void
    {
        $line = MarkdownUtils::createCommentLine($comment, $content);
        // 把格式改标准一些.
        $this->parser
            ->currentSection
            ->appendLine($line);
    }



}
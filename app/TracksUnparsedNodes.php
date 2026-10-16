<?php

namespace App;

/**
 * Keeps track of the elements and attributes a DOM based parser has read, so that everything else in the document
 * can be reported (and logged) as unparsed.
 *
 * Expects the using class to have $document (DOMDocument), $log (logger) and $info (log context) properties.
 *
 * @package App
 */
trait TracksUnparsedNodes
{
    /**
     * Node paths (DOMNode::getNodePath()) of every element and attribute the parser has read.
     * @see getUnparsed()
     */
    protected $consumed = [];

    /**
     * Mark a node and its ancestors as read by the parser.
     *
     * @param \DOMNode $node
     * @param bool $withDescendants  true if the node's text content was read (descendants are covered by it)
     * @return \DOMNode
     */
    protected function consume(\DOMNode $node, $withDescendants = false) {
        $parent = $node instanceof \DOMAttr ? $node->ownerElement : $node;

        for ($n = $node; $n && !($n instanceof \DOMDocument); $n = $n instanceof \DOMAttr ? $n->ownerElement : $n->parentNode) {
            $this->consumed[$n->getNodePath()] = true;
        }

        if ($withDescendants && $parent instanceof \DOMElement) {
            foreach ($parent->getElementsByTagName('*') as $descendant) {
                $this->consumed[$descendant->getNodePath()] = true;
            }
        }

        return $node;
    }

    /**
     * Elements and attributes present in the document that the parser never read, as XPaths without positions
     * (e.g. /KD_8_1_Z2/PROCEDURE/FRAMEWORK/SINGLE_OPERATOR). Only the topmost unread element is listed,
     * its descendants are implied. Elements without text and attributes get an ' (empty)' suffix; they are still
     * listed because marker elements like <SINGLE_OPERATOR/> carry meaning by their presence alone.
     *
     * @return string[]
     */
    public function getUnparsed() {
        $result = [];

        $xpath = new \DOMXPath($this->document);

        foreach ($xpath->query('//*') as $element) {
            if (isset($this->consumed[$element->getNodePath()])) {
                foreach ($element->attributes as $attribute) {
                    if ($this->ignoresUnparsedAttribute($attribute)) {
                        continue;
                    }
                    if (!isset($this->consumed[$attribute->getNodePath()])) {
                        $result[] = $this->genericPath($element) . '/@' . $attribute->name;
                    }
                }
                continue;
            }

            // only report the topmost unread element
            if ($element->parentNode instanceof \DOMElement && !isset($this->consumed[$element->parentNode->getNodePath()])) {
                continue;
            }

            $isEmpty = trim($element->textContent) === '' && !$xpath->query('.//@*', $element)->length;

            $result[] = $this->genericPath($element) . ($isEmpty ? ' (empty)' : '');
        }

        return array_values(array_unique($result));
    }

    /**
     * Attributes that are no content and never reported. Override to add format specific metadata attributes.
     *
     * @param \DOMAttr $attribute
     * @return bool
     */
    protected function ignoresUnparsedAttribute(\DOMAttr $attribute) {
        // schema location et al.
        return $attribute->namespaceURI === 'http://www.w3.org/2001/XMLSchema-instance';
    }

    /**
     * @param \DOMElement $element
     * @return string  path of local names, without namespace prefixes and positions
     */
    protected function genericPath(\DOMElement $element) {
        $names = [];

        for ($n = $element; $n instanceof \DOMElement; $n = $n->parentNode) {
            array_unshift($names, $n->localName);
        }

        return '/' . join('/', $names);
    }

    /**
     * @param string $format  name of the source format for the log message, e.g. 'Kerndaten'
     */
    protected function logUnparsed($format) {
        $unparsed = $this->getUnparsed();

        if ($unparsed) {
            $this->log->warning("$format record contains unparsed elements", array_merge((array)$this->info, ['unparsed' => $unparsed]));
        }
    }
}

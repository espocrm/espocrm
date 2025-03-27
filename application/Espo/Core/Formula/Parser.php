<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Formula;

use Espo\Core\Formula\Exceptions\SyntaxError;
use Espo\Core\Formula\Parser\Ast\Attribute;
use Espo\Core\Formula\Parser\Ast\Node;
use Espo\Core\Formula\Parser\Ast\Value;
use Espo\Core\Formula\Parser\Ast\Variable;
use Espo\Core\Formula\Parser\Statement\IfRef;
use Espo\Core\Formula\Parser\Statement\StatementRef;

use Espo\Core\Formula\Parser\Statement\WhileRef;
use LogicException;

/**
 * Parses a formula-script into AST.
 */
class Parser
{
    /** @var array<int, string[]> */
    private array $priorityList = [
        ['='],
        ['??'],
        ['||'],
        ['&&'],
        ['==', '!=', '>', '<', '>=', '<='],
        ['+', '-'],
        ['*', '/', '%'],
    ];

    /** @var array<string, string> */
    private array $operatorMap = [
        '=' => 'assign',
        '??' => 'comparison\\nullCoalescing',
        '||' => 'logical\\or',
        '&&' => 'logical\\and',
        '+' => 'numeric\\summation',
        '-' => 'numeric\\subtraction',
        '*' => 'numeric\\multiplication',
        '/' => 'numeric\\division',
        '%' => 'numeric\\modulo',
        '==' => 'comparison\\equals',
        '!=' => 'comparison\\notEquals',
        '>' => 'comparison\\greaterThan',
        '<' => 'comparison\\lessThan',
        '>=' => 'comparison\\greaterThanOrEquals',
        '<=' => 'comparison\\lessThanOrEquals',
    ];

    /** @var string[] */
    private array $whiteSpaceCharList = [
        "\r",
        "\n",
        "\t",
        ' ',
    ];

    private string $variableNameRegExp = "/^[a-zA-Z0-9_\$]+$/";
    private string $functionNameRegExp = "/^[a-zA-Z0-9_\\\\]+$/";
    private string $attributeNameRegExp = "/^[a-zA-Z0-9.]+$/";

    /**
     * @throws SyntaxError
     */
    public function parse(string $expression): Node|Attribute|Variable|Value
    {
        return $this->split($expression, true);
    }

    /**
     * @throws SyntaxError
     */
    private function applyOperator(string $operator, string $firstPart, string $secondPart): Node
    {
        if ($operator === '=') {
            if (!strlen($firstPart)) {
                throw new SyntaxError("Bad operator usage.");
            }

            if ($firstPart[0] == '$') {
                return $this->applyOperatorVariableAssign($firstPart, $secondPart);
            }

            if ($secondPart === '') {
                throw SyntaxError::create("Bad assignment usage.");
            }

            return new Node('setAttribute', [
                new Value($firstPart),
                $this->split($secondPart)
            ]);
        }

        $functionName = $this->operatorMap[$operator];

        if ($functionName === '' || !preg_match($this->functionNameRegExp, $functionName)) {
            throw new SyntaxError("Bad function name `$functionName`.");
        }

        return new Node($functionName, [
            $this->split($firstPart),
            $this->split($secondPart),
        ]);
    }

    private static function isNotAfterBackslash(string $string, int $i): bool
    {
        return
            ($string[$i - 1] ?? null) !== "\\" ||
            (($string[$i - 2] ?? null) === "\\" && ($string[$i - 3] ?? null) !== "\\");
    }

    /**
     * @param string $string An expression. Comments will be stripped by the method.
     * @param string $modifiedString A modified expression with removed parentheses and braces inside strings.
     * @param ?((StatementRef|IfRef|WhileRef)[]) $statementList Statements will be added if there are multiple.
     * @throws SyntaxError
     */
    private function processString(
        string &$string,
        string &$modifiedString,
        ?array &$statementList = null,
        bool $intoOneLine = false
    ): bool {

        $isString = false;
        $isSingleQuote = false;
        $isComment = false;
        $isLineComment = false;
        $parenthesisCounter = 0;
        $braceCounter = 0;
        $bracketCounter = 0;

        $modifiedString = $string;

        for ($i = 0; $i < strlen($string); $i++) {
            $isStringStart = false;
            $char = $string[$i];
            $isLast = $i === strlen($string) - 1;

            if (!$isLineComment && !$isComment) {
                if ($string[$i] === "'" && self::isNotAfterBackslash($string, $i)) {
                    if (!$isString) {
                        $isString = true;
                        $isStringStart = true;
                        $isSingleQuote = true;
                    } else if ($isSingleQuote) {
                        $isString = false;
                    }
                } else if ($string[$i] === "\"" && self::isNotAfterBackslash($string, $i)) {
                    if (!$isString) {
                        $isString = true;
                        $isStringStart = true;
                        $isSingleQuote = false;
                    } else if (!$isSingleQuote) {
                        $isString = false;
                    }
                }
            }

            if ($isString) {
                if (in_array($char, ['(', ')', '{', '}', '[', ']'])) {
                    $modifiedString[$i] = '_';
                } else if (!$isStringStart) {
                    $modifiedString[$i] = ' ';
                }

                continue;
            }

            $isLineCommentEnding = $isLineComment && ($string[$i] === "\n" || $isLast);
            $isCommentEnding = $isComment && $string[$i] === "*" && $string[$i + 1] === "/";

            if ($isCommentEnding) {
                $string[$i + 1] = ' ';
                $modifiedString[$i + 1] = ' ';
            }

            if ($isLineComment || $isComment) {
                $string[$i] = ' ';
                $modifiedString[$i] = ' ';
            }

            if (!$isLineComment && !$isComment) {
                if (!$isLast && $string[$i] === '/' && $string[$i + 1] === '/') {
                    $isLineComment = true;

                    $string[$i] = ' ';
                    $string[$i + 1] = ' ';
                    $modifiedString[$i] = ' ';
                    $modifiedString[$i + 1] = ' ';
                }

                if (!$isLineComment) {
                    if (!$isLast && $string[$i] === '/' && $string[$i + 1] === '*') {
                        $isComment = true;

                        $string[$i] = ' ';
                        $string[$i + 1] = ' ';
                        $modifiedString[$i] = ' ';
                        $modifiedString[$i + 1] = ' ';
                    }
                }

                if ($char === '(') {
                    $parenthesisCounter++;
                } else if ($char === ')') {
                    $parenthesisCounter--;
                } else if ($char === '{') {
                    $braceCounter++;
                } else if ($char === '}') {
                    $braceCounter--;
                } else if ($char === '[') {
                    $bracketCounter++;
                } else if ($char === ']') {
                    $bracketCounter--;
                }
            }

            if ($statementList !== null) {
                $this->processStringIteration(
                    string: $string,
                    i: $i,
                    statementList: $statementList,
                    parenthesisCounter: $parenthesisCounter,
                    braceCounter: $braceCounter,
                    bracketCounter: $bracketCounter,
                    isLineComment: $isLineComment,
                    isComment: $isComment,
                );
            }

            if ($intoOneLine) {
                if (
                    $parenthesisCounter === 0 &&
                    $this->isWhiteSpace($char) &&
                    $char !== ' '
                ) {
                    $string[$i] = ' ';
                }
            }

            if ($isLineCommentEnding) {
                $isLineComment = false;
            }

            if ($isCommentEnding) {
                $isComment = false;
            }

            /*if ($isLineComment) {
                if ($string[$i] === "\n") {
                    $isLineComment = false;
                }
            }

            if ($isComment) {
                if ($string[$i - 1] === "*" && $string[$i] === "/") {
                    $isComment = false;
                }
            }*/
        }

        if ($statementList !== null) {
            $lastStatement = end($statementList);

            if (
                $lastStatement instanceof StatementRef &&
                count($statementList) === 1 &&
                !$lastStatement->isEndedWithSemicolon()
            ) {
                array_pop($statementList);
            } else if (
                $lastStatement instanceof StatementRef &&
                !$lastStatement->isEndedWithSemicolon()
            ) {
                $lastStatement->setEnd(strlen($string));
            }
        }

        return $isString;
    }

    /**
     * @param (StatementRef|IfRef|WhileRef)[] $statementList
     * @throws SyntaxError
     */
    private function processStringIteration(
        string $string,
        int &$i,
        array &$statementList,
        int $parenthesisCounter,
        int $braceCounter,
        int $bracketCounter,
        bool $isLineComment,
        bool $isComment,
    ): void {

        $char = $string[$i];
        $isLast = $i === strlen($string) - 1;

        $lastStatement = count($statementList) ?
            end($statementList) : null;

        if (
            $lastStatement instanceof StatementRef &&
            !$lastStatement->isReady()
        ) {
            if (
                $parenthesisCounter === 0 &&
                $braceCounter === 0 &&
                $bracketCounter === 0
            ) {
                if ($char === ';') {
                    $lastStatement->setEnd($i, true);

                    return;
                }

                if ($isLast) {
                    $lastStatement->setEnd($i + 1);

                    return;
                }
            }
        }

        if (
            $lastStatement instanceof IfRef &&
            !$lastStatement->isReady()
        ) {
            $toContinue = $this->processStringIfStatement(
                string: $string,
                i: $i,
                parenthesisCounter: $parenthesisCounter,
                braceCounter: $braceCounter,
                statement: $lastStatement,
            );

            if ($toContinue) {
                return;
            }
        }

        if (
            $lastStatement instanceof WhileRef &&
            !$lastStatement->isReady()
        ) {
            $toContinue = $this->processStringWhileStatement(
                $string,
                $i,
                $parenthesisCounter,
                $braceCounter,
                $lastStatement
            );

            if ($toContinue === null) {
                // Not a `while` statement, but likely a `while` function.
                array_pop($statementList);

                $lastStatement = new StatementRef($lastStatement->getStart());
                $statementList[] = $lastStatement;

                if ($char === ';') {
                    $lastStatement->setEnd($i, true);

                    return;
                }
            }

            if ($toContinue) {
                return;
            }
        }

        if (
            (
                $parenthesisCounter === 0 ||
                $parenthesisCounter === 1 && $char === '('
            ) &&
            $braceCounter === 0 &&
            $bracketCounter === 0
        ) {
            if ($isLineComment || $isComment) {
                return;
            }

            $previousStatementEnd = $lastStatement ?
                $lastStatement->getEnd() :
                -1;

            if (
                $lastStatement &&
                !$lastStatement->isReady()
            ) {
                return;
            }

            if ($previousStatementEnd === null) {
                throw SyntaxError::create("Incorrect statement usage.");
            }

            if ($this->isOnIf($string, $i)) {
                $statementList[] = new IfRef();

                $i += 1;

                return;
            }

            if ($this->isOnWhile($string, $i)) {
                $statementList[] = new WhileRef($i);

                $i += 4;

                return;
            }

            if (
                !$this->isWhiteSpace($char) &&
                $char !== ';' &&
                $char !== '/'
            ) {
                $statementList[] = new StatementRef($i);
            }
        }
    }

    private function processStringIfStatement(
        string $string,
        int &$i,
        int $parenthesisCounter,
        int $braceCounter,
        IfRef $statement
    ): bool {

        $char = $string[$i];
        $isLast = $i === strlen($string) - 1;

        if (
            $char === '(' &&
            !$isLast &&
            $parenthesisCounter === 1 &&
            $braceCounter === 0 &&
            $statement->getState() === IfRef::STATE_EMPTY
        ) {
            $statement->setConditionStart($i + 1);

            return true;
        }

        if (
            $char === ')' &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $statement->getState() === IfRef::STATE_CONDITION_STARTED
        ) {
            $statement->setConditionEnd($i);

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_CONDITION_ENDED &&
            !$isLast &&
            $parenthesisCounter === 0 &&
            $braceCounter === 1 &&
            $char === '{'
        ) {
            $statement->setThenStart($i + 1);

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_THEN_STARTED &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $char === '}'
        ) {
            $statement->setThenEnd($i);

            if ($isLast) {
                $statement->setReady();
            }

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_THEN_ENDED &&
            $this->isWhiteSpace($char) &&
            $isLast
        ) {
            $statement->setReady();

            // No need to call continue.
            return false;
        }

        if (
            $statement->getState() === IfRef::STATE_THEN_ENDED &&
            !$this->isWhiteSpace($char) &&
            !$this->isOnElse($string, $i)
        ) {
            $statement->setReady();

            // No need to call continue.
            return false;
        }

        if (
            $statement->getState() === IfRef::STATE_THEN_ENDED &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $this->isOnElse($string, $i)
        ) {
            $statement->setElseMet($i + 4);

            $i += 3;

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_ELSE_MET &&
            !$isLast &&
            $parenthesisCounter === 0 &&
            $braceCounter === 1 &&
            $char === '{'
        ) {
            $statement->setElseStart($i + 1);

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_ELSE_MET &&
            !$isLast &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $this->isWhiteSpace($string[$i - 1]) &&
            $this->isOnIf($string, $i)
        ) {
            $statement->setElseStart($i, true);

            $i += 1;

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_ELSE_STARTED &&
            $statement->hasInlineElse() &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $char === '}'
        ) {
            $elseFound = false;
            $j = $i + 1;

            while ($j < strlen($string)) {
                if ($this->isWhiteSpace($string[$j])) {
                    $j++;

                    continue;
                }

                $elseFound = $this->isOnElse($string, $j);

                break;
            }

            if (!$elseFound) {
                $statement->setElseEnd($i + 1);
                $statement->setReady();
            }

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_ELSE_STARTED &&
            !$statement->hasInlineElse() &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $char === '}'
        ) {
            $statement->setElseEnd($i);
            $statement->setReady();

            return true;
        }

        if (
            $statement->getState() === IfRef::STATE_ELSE_MET &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $char === '}'
        ) {
            $statement->setElseStart($statement->getElseKeywordEnd() + 1);
            $statement->setElseEnd($i + 1);
            $statement->setReady();

            return true;
        }

        return false;
    }

    private function processStringWhileStatement(
        string $string,
        int $i,
        int $parenthesisCounter,
        int $braceCounter,
        WhileRef $statement
    ): ?bool {

        $char = $string[$i];
        $isLast = $i === strlen($string) - 1;

        if (
            $char === '(' &&
            !$isLast &&
            $parenthesisCounter === 1 &&
            $braceCounter === 0 &&
            $statement->getState() === WhileRef::STATE_EMPTY
        ) {
            $statement->setConditionStart($i + 1);

            return true;
        }

        if (
            $char === ')' &&
            !$isLast &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $statement->getState() === WhileRef::STATE_CONDITION_STARTED
        ) {
            $statement->setConditionEnd($i);

            return true;
        }

        if (
            $statement->getState() === WhileRef::STATE_CONDITION_ENDED &&
            !$isLast &&
            $parenthesisCounter === 0 &&
            $braceCounter === 1 &&
            $char === '{'
        ) {
            $statement->setBodyStart($i + 1);

            return true;
        }

        if (
            $statement->getState() === WhileRef::STATE_CONDITION_STARTED &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $char === ')' &&
            $isLast
        ) {
            return null;
        }

        if (
            $statement->getState() === WhileRef::STATE_CONDITION_ENDED &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            (
                $isLast ||
                !$this->isWhiteSpace($char)
            )
        ) {
            return null;
        }

        if (
            $statement->getState() === WhileRef::STATE_BODY_STARTED &&
            $parenthesisCounter === 0 &&
            $braceCounter === 0 &&
            $char === '}'
        ) {
            $statement->setBodyEnd($i);

            return true;
        }

        return false;
    }

    private function isOnIf(string $string, int $i): bool
    {
        $before = substr($string, $i - 1, 1);
        $after = substr($string, $i + 2, 1);

        return
            substr($string, $i, 2) === 'if' &&
            (
                $i === 0 ||
                $this->isWhiteSpace($before) ||
                $before === ';'
            ) &&
            (
                $this->isWhiteSpace($after) ||
                $after === '('
            );
    }

    private function isOnElse(string $string, int $i): bool
    {
        return substr($string, $i, 4) === 'else' &&
            $this->isWhiteSpaceCharOrBraceOpen(substr($string, $i + 4, 1)) &&
            $this->isWhiteSpaceCharOrBraceClose(substr($string, $i - 1, 1));
    }

    private function isOnWhile(string $string, int $i): bool
    {
        $before = substr($string, $i - 1, 1);
        $after = substr($string, $i + 5, 1);

        return
            substr($string, $i, 5) === 'while' &&
            (
                $i === 0 ||
                $this->isWhiteSpace($before) ||
                $before === ';'
            ) &&
            (
                $this->isWhiteSpace($after) ||
                $after === '('
            );
    }

    private function isWhiteSpaceCharOrBraceOpen(string $char): bool
    {
        return $char === '{' || in_array($char, $this->whiteSpaceCharList);
    }

    private function isWhiteSpaceCharOrBraceClose(string $char): bool
    {
        return $char === '}' || in_array($char, $this->whiteSpaceCharList);
    }

    private function isWhiteSpace(string $char): bool
    {
        return in_array($char, $this->whiteSpaceCharList);
    }

    /**
     * @throws SyntaxError
     */
    private function split(string $expression, bool $isRoot = false): Node|Attribute|Variable|Value
    {
        $expression = trim($expression);

        $parenthesisCounter = 0;
        $braceCounter = 0;
        $bracketCounter = 0;
        $hasExcessParenthesis = true;
        $modifiedExpression = '';
        $topLevelExpressionList = [];

        $statementList = [];

        $isStringNotClosed = $this->processString($expression, $modifiedExpression, $statementList, true);

        if ($isStringNotClosed) {
            throw SyntaxError::create('String is not closed.');
        }

        $expressionLength = strlen($modifiedExpression);

        for ($i = 0; $i < $expressionLength; $i++) {
            $value = $modifiedExpression[$i];

            if ($value === '(') {
                $parenthesisCounter++;
            } else if ($value === ')') {
                $parenthesisCounter--;
            } else if ($value === '{') {
                $braceCounter++;
            } else if ($value === '}') {
                $braceCounter--;
            } else if ($value === '[') {
                $bracketCounter++;
            } else if ($value === ']') {
                $bracketCounter--;
            }

            if ($parenthesisCounter === 0 && $i < $expressionLength - 1) {
                $hasExcessParenthesis = false;
            }

            $topLevelExpressionList[] = $parenthesisCounter === 0 && $bracketCounter === 0;
        }

        if ($parenthesisCounter !== 0) {
            throw SyntaxError::create(
                'Incorrect parentheses usage in expression ' . $expression . '.',
                'Incorrect parentheses.'
            );
        }

        if ($braceCounter !== 0) {
            throw SyntaxError::create(
                'Incorrect braces usage in expression ' . $expression . '.',
                'Incorrect braces.'
            );
        }

        if ($bracketCounter !== 0) {
            throw SyntaxError::create(
                'Incorrect bracket usage in expression ' . $expression . '.',
                'Incorrect brackets.'
            );
        }

        if (
            strlen($expression) > 1 &&
            $expression[0] === '(' &&
            $expression[strlen($expression) - 1] === ')' &&
            $hasExcessParenthesis
        ) {
            $expression = substr($expression, 1, strlen($expression) - 2);

            return $this->split($expression, true);
        }

        if ($statementList !== null && count($statementList)) {
            return $this->processStatementList($expression, $statementList, $isRoot);
        }

        $firstOperator = null;
        $minIndex = null;

        if (trim($expression) === '') {
            return new Value(null);
        }

        foreach ($this->priorityList as $operationList) {
            foreach ($operationList as $operator) {
                $offset = -1;

                while (true) {
                    $index = strrpos($modifiedExpression, $operator, $offset);

                    if ($index === false) {
                        break;
                    }

                    if (
                        $topLevelExpressionList[$index] &&
                        !$this->isAtAnotherOperator($index, $operator, $modifiedExpression)
                    ) {
                        break;
                    }

                    $offset = -(strlen($expression) - $index) - 1;
                }

                if ($index === false) {
                    continue;
                }

                if ($operator === '+' || $operator === '-') {
                    $j = $index - 1;

                    while ($j >= 0) {
                        $char = $expression[$j];

                        if ($this->isWhiteSpace($char)) {
                            $j--;

                            continue;
                        }

                        if (array_key_exists($char, $this->operatorMap)) {
                            continue 2;
                        }

                        break;
                    }
                }

                $firstPart = substr($expression, 0, $index);
                $secondPart = substr($expression, $index + strlen($operator));

                $modifiedFirstPart = $modifiedSecondPart = '';

                $isString = $this->processString($firstPart, $modifiedFirstPart);

                $this->processString($secondPart, $modifiedSecondPart);

                if (
                    substr_count($modifiedFirstPart, '(') === substr_count($modifiedFirstPart, ')') &&
                    substr_count($modifiedSecondPart, '(') === substr_count($modifiedSecondPart, ')') &&
                    !$isString
                ) {
                    if ($minIndex === null || $index > $minIndex) {
                        $minIndex = $index;

                        $firstOperator = $operator;
                    }
                }
            }

            if ($firstOperator) {
                break;
            }
        }

        if ($firstOperator) {
            /** @var int $minIndex */

            $firstPart = substr($expression, 0, $minIndex);
            $secondPart = substr($expression, $minIndex + strlen($firstOperator));

            $firstPart = trim($firstPart);
            $secondPart = trim($secondPart);

            return $this->applyOperator($firstOperator, $firstPart, $secondPart);
        }

        $expression = trim($expression);

        if ($expression[0] === '!') {
            return new Node('logical\\not', [
                $this->split(substr($expression, 1))
            ]);
        }

        if ($expression[0] === '-') {
            return new Node('numeric\\subtraction', [
                new Value(0),
                $this->split(substr($expression, 1))
            ]);
        }

        if ($expression[0] === '+') {
            return new Node('numeric\\summation', [
                new Value(0),
                $this->split(substr($expression, 1))
            ]);
        }

        if (
            $expression[0] === "'" && $expression[strlen($expression) - 1] === "'" ||
            $expression[0] === "\"" && $expression[strlen($expression) - 1] === "\""
        ) {
            return new Value(self::prepareStringValue($expression));
        }

        if ($expression[0] === "$") {
            return $this->splitVariable($expression);
        }

        if (is_numeric($expression)) {
            $value = filter_var($expression, FILTER_VALIDATE_INT) !== false ?
                (int) $expression :
                (float) $expression;

            return new Value($value);
        }

        if ($expression === 'true') {
            return new Value(true);
        }

        if ($expression === 'false') {
            return new Value(false);
        }

        if ($expression === 'null') {
            return new Value(null);
        }

        if ($expression === 'break') {
            return new Node('break', []);
        }

        if ($expression === 'continue') {
            return new Node('continue', []);
        }

        if ($expression[strlen($expression) - 1] === ')') {
            $firstOpeningBraceIndex = strpos($expression, '(');

            if ($firstOpeningBraceIndex > 0) {
                $functionName = trim(substr($expression, 0, $firstOpeningBraceIndex));
                $functionContent = substr($expression, $firstOpeningBraceIndex + 1, -1);

                $argumentList = $this->parseArgumentListFromFunctionContent($functionContent);

                $argumentSplitList = [];

                foreach ($argumentList as $argument) {
                    $argumentSplitList[] = $this->split($argument);
                }

                if ($functionName === '' || !preg_match($this->functionNameRegExp, $functionName)) {
                    throw new SyntaxError("Bad function name `$functionName`.");
                }

                return new Node($functionName, $argumentSplitList);
            }
        }

        if (str_contains($expression, ' ')) {
            throw SyntaxError::create("Could not parse.");
        }

        if (!preg_match($this->attributeNameRegExp, $expression)) {
            throw SyntaxError::create("Attribute name `$expression` contains not allowed characters.");
        }

        if (str_ends_with($expression, '.')) {
            throw SyntaxError::create("Attribute ends with dot.");
        }

        return new Attribute($expression);
    }

    private function isAtAnotherOperator(int $index, string $operator, string $expression): bool
    {
        $possibleRightOperator = null;

        if (strlen($operator) === 1) {
            if ($index < strlen($expression) - 1) {
                $possibleRightOperator = trim($operator . $expression[$index + 1]);
            }
        }

        if (
            $possibleRightOperator &&
            $possibleRightOperator != $operator &&
            !empty($this->operatorMap[$possibleRightOperator])
        ) {
            return true;
        }

        $possibleLeftOperator = null;

        if (strlen($operator) === 1) {
            if ($index > 0) {
                $possibleLeftOperator = trim($expression[$index - 1] . $operator);
            }
        }

        if (
            $possibleLeftOperator &&
            $possibleLeftOperator != $operator &&
            !empty($this->operatorMap[$possibleLeftOperator])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param (StatementRef|IfRef|WhileRef)[] $statementList
     * @throws SyntaxError
     */
    private function processStatementList(
        string $expression,
        array $statementList,
        bool $isRoot
    ): Node|Value|Attribute|Variable {

        $parsedPartList = [];

        foreach ($statementList as $statement) {
            $parsedPart = null;

            if ($statement instanceof StatementRef) {
                $start = $statement->getStart();
                $end = $statement->getEnd();

                if ($end === null) {
                    throw new LogicException();
                }

                $part = self::sliceByStartEnd($expression, $start, $end);

                $parsedPart = $this->split($part);
            } else if ($statement instanceof IfRef) {
                if (!$isRoot || !$statement->isReady()) {
                    throw SyntaxError::create(
                        'Incorrect if statement usage in expression ' . $expression . '.',
                        'Incorrect if statement.'
                    );
                }

                $conditionStart = $statement->getConditionStart();
                $conditionEnd = $statement->getConditionEnd();
                $thenStart = $statement->getThenStart();
                $thenEnd = $statement->getThenEnd();
                $elseStart = $statement->getElseStart();
                $elseEnd = $statement->getElseEnd();

                if (
                    $conditionStart === null ||
                    $conditionEnd === null ||
                    $thenStart === null ||
                    $thenEnd === null
                ) {
                    throw new LogicException();
                }

                $conditionPart = self::sliceByStartEnd($expression, $conditionStart, $conditionEnd);
                $thenPart = self::sliceByStartEnd($expression, $thenStart, $thenEnd);
                $elsePart = $elseStart !== null && $elseEnd !== null ?
                    self::sliceByStartEnd($expression, $elseStart, $elseEnd) : null;

                $parsedPart = $statement->getElseKeywordEnd() ?
                    new Node('ifThenElse', [
                        $this->split($conditionPart),
                        $this->split($thenPart, true),
                        $this->split($elsePart ?? '', true)
                    ]) :
                    new Node('ifThen', [
                        $this->split($conditionPart),
                        $this->split($thenPart, true)
                    ]);
            } else if ($statement instanceof WhileRef) {
                if (!$isRoot || !$statement->isReady()) {
                    throw SyntaxError::create(
                        'Incorrect while statement usage in expression ' . $expression . '.',
                        'Incorrect while statement.'
                    );
                }

                $conditionStart = $statement->getConditionStart();
                $conditionEnd = $statement->getConditionEnd();
                $bodyStart = $statement->getBodyStart();
                $bodyEnd = $statement->getBodyEnd();

                if (
                    $conditionStart === null ||
                    $conditionEnd === null ||
                    $bodyStart === null ||
                    $bodyEnd === null
                ) {
                    throw new LogicException();
                }

                $conditionPart = self::sliceByStartEnd($expression, $conditionStart, $conditionEnd);
                $bodyPart = self::sliceByStartEnd($expression, $bodyStart, $bodyEnd);

                $parsedPart = new Node('while', [
                    $this->split($conditionPart),
                    $this->split($bodyPart, true)
                ]);
            }

            if (!$parsedPart) {
                throw SyntaxError::create(
                    'Unknown syntax error in expression ' . $expression . '.',
                    'Unknown syntax error.'
                );
            }

            $parsedPartList[] = $parsedPart;
        }

        if (count($parsedPartList) === 1) {
            return $parsedPartList[0];
        }

        return new Node('bundle', $parsedPartList);
    }

    private static function sliceByStartEnd(string $expression, int $start, int $end): string
    {
        return trim(
            substr(
                $expression,
                $start,
                $end - $start
            )
        );
    }

    /**
     * @return string[]
     */
    private function parseArgumentListFromFunctionContent(string $functionContent): array
    {
        $functionContent = trim($functionContent);

        $isString = false;
        $isSingleQuote = false;

        if ($functionContent === '') {
            return [];
        }

        $commaIndexList = [];
        $braceCounter = 0;

        for ($i = 0; $i < strlen($functionContent); $i++) {
            if ($functionContent[$i] === "'" && self::isNotAfterBackslash($functionContent, $i)) {
                if (!$isString) {
                    $isString = true;
                    $isSingleQuote = true;
                } else {
                    if ($isSingleQuote) {
                        $isString = false;
                    }
                }
            } else if ($functionContent[$i] === "\"" && self::isNotAfterBackslash($functionContent, $i)) {
                if (!$isString) {
                    $isString = true;
                    $isSingleQuote = false;
                } else {
                    if (!$isSingleQuote) {
                        $isString = false;
                    }
                }
            }

            if (!$isString) {
                if ($functionContent[$i] === '(') {
                    $braceCounter++;
                } else if ($functionContent[$i] === ')') {
                    $braceCounter--;
                }
            }

            if ($braceCounter === 0 && !$isString && $functionContent[$i] === ',') {
                $commaIndexList[] = $i;
            }
        }

        $commaIndexList[] = strlen($functionContent);

        $argumentList = [];

        for ($i = 0; $i < count($commaIndexList); $i++) {
            if ($i > 0) {
                $previousCommaIndex = $commaIndexList[$i - 1] + 1;
            } else {
                $previousCommaIndex = 0;
            }

            $argument = trim(
                substr(
                    $functionContent,
                    $previousCommaIndex,
                    $commaIndexList[$i] - $previousCommaIndex
                )
            );

            $argumentList[] = $argument;
        }

        return $argumentList;
    }

    static private function prepareStringValue(string $expression): string
    {
        $string = substr($expression, 1, strlen($expression) - 2);

        $isDoubleQuote = $expression[0] === '"';

        /** @var array{bool, string}[] $tokens */
        $tokens = [];

        $stripList = ["\\\\", "\\\"", "\\n", "\\t", "\\r"];
        $replaceList = ["\\",  "\"", "\n", "\t", "\r"];

        if ($isDoubleQuote) {
            $stripList[] = "\\\"";
            $replaceList[] = "\"";
        } else {
            $stripList[] = "\\'";
            $replaceList[] = "'";
        }

        $k = 0;

        for ($i = 0; $i < strlen($string); $i++) {
            $part = substr($string, $i, 2);

            if (in_array($part, $stripList)) {
                $len = strlen($part);

                $before = substr($string, $k, $i - $k);

                if (strlen($before)) {
                    $tokens[] = [false, $before];
                }

                $tokens[] = [true, $part];

                $i += $len - 1;
                $k = $i + 1;
            }

            if ($i >= strlen($string) - 1) {
                $after = substr($string, $k);

                if (strlen($after)) {
                    $tokens[] = [false, $after];
                }
            }
        }

        $result = '';

        foreach ($tokens as $token) {
            if (!$token[0]) {
                $result .= $token[1];

                continue;
            }

            $result .= str_replace($stripList, $replaceList, $token[1]);
        }

        return $result;
    }

    /**
     * @throws SyntaxError
     */
    private function applyOperatorVariableAssign(string $firstPart, string $secondPart): Node
    {
        $variable = substr($firstPart, 1);

        $isArrayAppend = false;
        $isKeyValue = false;
        $keyPath = [];

        if (str_ends_with($firstPart, '[]')) {
            $variable = substr($firstPart, 1, -2);

            $isArrayAppend = true;
        } else if (str_ends_with($firstPart, ']') && str_contains($firstPart, '[')) {
            $bracketPosition = strpos($firstPart, '[') ?: 0;

            $variable = substr($firstPart, 1, $bracketPosition - 1);

            $keyPart = trim(substr($firstPart, $bracketPosition));
            $keyPath = array_map(fn ($it) => $this->split($it), $this->splitKeys($keyPart));

            $isKeyValue = true;
        }

        if ($variable === '' || !preg_match($this->variableNameRegExp, $variable)) {
            throw new SyntaxError("Bad variable name `$variable`.");
        }

        if ($isArrayAppend) {
            return new Node('arrayAppend', [
                new Value($variable),
                $this->split($secondPart)
            ]);
        }

        if ($isKeyValue) {
            return new Node('variableSetKeyValue', [
                new Value($variable),
                new Node('list', $keyPath),
                $this->split($secondPart)
            ]);
        }

        return new Node('assign', [
            new Value($variable),
            $this->split($secondPart)
        ]);
    }

    /**
     * @throws SyntaxError
     */
    private function splitVariable(string $expression): Node|Variable
    {
        $value = substr($expression, 1);

        $isIncrement = false;
        $isDecrement = false;
        $isKeyValue = false;
        $keyPath = [];

        if (str_ends_with($expression, '++')) {
            $isIncrement = true;

            $value = rtrim(substr($value, 0, -2));
        }

        if (str_ends_with($expression, '--')) {
            $isDecrement = true;

            $value = rtrim(substr($value, 0, -2));
        } else if (str_ends_with($expression, ']') && str_contains($expression, '[')) {
            $bracketPosition = strpos($expression, '[') ?: 0;
            $value = substr($expression, 1, $bracketPosition - 1);
            $keyPart = trim(substr($expression, $bracketPosition));
            $keyPath = array_map(fn ($it) => $this->split($it), $this->splitKeys($keyPart));

            $isKeyValue = true;
        }

        if ($value === '' || !preg_match($this->variableNameRegExp, $value)) {
            throw new SyntaxError("Bad variable name `$value`.");
        }

        if ($isIncrement) {
            return new Node('variableIncrement', [
                new Value($value),
            ]);
        }

        if ($isDecrement) {
            return new Node('variableDecrement', [
                new Value($value),
            ]);
        }

        if ($isKeyValue) {
            return new Node('variableGetValueByKey', [
                new Value($value),
                new Node('list', $keyPath),
            ]);
        }

        return new Variable($value);
    }

    /**
     * @return string[]
     * @throws SyntaxError
     */
    private function splitKeys(string $expression): array
    {
        $modifiedExpression = '';

        $this->processString($expression, $modifiedExpression, $statementList, true);

        $expressionLength = strlen($modifiedExpression);

        $parenthesisCounter = 0;
        $bracketCounter = 0;

        $output = [];

        /** @var array{int, int}[] $indexPairs */
        $indexPairs = [];

        $startIndex = -1;

        for ($i = 0; $i < $expressionLength; $i++) {
            $value = $modifiedExpression[$i];

            if ($value === '(') {
                $parenthesisCounter++;
            } else if ($value === ')') {
                $parenthesisCounter--;
            } else if ($value === '[') {
                $bracketCounter++;
            } else if ($value === ']') {
                $bracketCounter--;
            }

            if (
                $value === '[' &&
                $parenthesisCounter === 0 &&
                $bracketCounter === 1
            ) {
                $startIndex = $i;
            }

            if (
                $value === ']' &&
                $parenthesisCounter === 0 &&
                $bracketCounter === 0
            ) {
                $indexPairs[] = [$startIndex + 1, $i];

                $startIndex = -1;
            }
        }

        foreach ($indexPairs as $i => $pair) {
            if ($i > 0) {
                if ($indexPairs[$i - 1][1] !== $pair[0] - 2) {
                    throw new SyntaxError("Nested brackets must have no gaps in between.");
                }
            }

            $itemExpression = trim(substr($expression, $pair[0], $pair[1] - $pair[0]));

            if ($itemExpression === '') {
                throw new SyntaxError("No expression inside brackets.");
            }

            $output[] = $itemExpression;
        }

        return $output;
    }
}

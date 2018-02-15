<?php

namespace Taeram\Bot;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="karma", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="karma", columns={"name", "type", "karma"})
 * })
 * @ORM\Entity
 */
class Karma extends \Taeram\Bot
{
    /**
     * The class name.
     *
     * @var string
     */
    protected static $className = __CLASS__;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     */
    protected $type;

    const TYPE_USER = 'user';
    const TYPE_THING = 'thing';

    /**
     * @var int
     *
     * @ORM\Column(name="karma", type="integer")
     */
    protected $karma;

    /**
     * @var string
     *
     * @ORM\Column(name="chat_id", type="integer")
     */
    protected $chat;

    /**
     * The karma regex (thing++, user++, something word++ something else).
     *
     * @var string
     */
    protected $regex = '/(?:' .
    '(?:(?:(@[\\x{00C0}-\\x{1FFF}\\x{2C00}-\\x{D7FF}\\w]+))\\s?)|' .
    '([\\x{00C0}-\\x{1FFF}\\x{2C00}-\\x{D7FF}\\w]+)|' .
    '(\\([\\x{00C0}-\\x{1FFF}\\x{2C00}-\\x{D7FF}\\w]+\\))|' .
    '(?:(["\'“])([^"“]+)[\'"”])' .
    ')(\\+{2,}|-{2,})( |$)/u';

    /**
     * Initialize the bot.
     *
     * @return self
     */
    public function initialize()
    {
        // Show the leaderboard for users and things
        $this->bot->hears('/karma top', function (\BotMan\BotMan\BotMan $bot) {
            // Make it look like the bot is typing
            $bot->types();

            $messages = $bot->getMessages();
            $chatId = $messages[0]->getRecipient();

            $topUsers = static::getTopResults(static::TYPE_USER, $chatId);
            $topThings = static::getTopResults(static::TYPE_THING, $chatId);
            $result = "<b>The users with the most karma:</b>\n";
            foreach ($topUsers as $i => $user) {
                $result .= str_pad((string) $user->getKarma(), 8, ' ', STR_PAD_LEFT) . '  ' . $user->getName() . "\n";
            }
            if (empty($topUsers)) {
                $result .= "No users found\n";
            }

            $result .= "\n<b>The things with the most karma:</b>\n";
            foreach ($topThings as $i => $thing) {
                $result .= str_pad((string) $thing->getKarma(), 9, ' ', STR_PAD_LEFT) . '  ' . $thing->getName() . "\n";
            }
            if (empty($topThings)) {
                $result .= str_pad("No things found\n", 9, ' ', STR_PAD_LEFT);
            }

            $bot->reply($result, ['parse_mode' => 'HTML']);
        });

        // Match karma increase / decreases
        $karmaRegex = $this->regex;
        $entity = $this;
        $this->bot->hears('(.+)', function (\BotMan\BotMan\BotMan $bot, $text) use ($karmaRegex, $entity) {
            if (preg_match_all($karmaRegex, $text, $matches, PREG_SET_ORDER)) {
                $messages = $bot->getMessages();
                $chatId = $messages[0]->getRecipient();

                // Make it look like the bot is typing
                $bot->types();

                $isProcessed = null;
                foreach ($matches as $match) {
                    $isMention = ($match[1] && $match[1][0] === '@');
                    $change = strlen($match[6]) - 1;
                    $maxed = false;
                    if ($change > 5) {
                        $change = 5;
                        $maxed = true;
                    }

                    if ($match[6][0] === '-') {
                        $change = -$change;
                    }

                    $value = null;
                    if ($isMention) {
                        $fromUsername = $bot->getUser()->getUsername();
                        $subject = str_replace('@', '', $match[1]);

                        // Don't let a user affect their own karma
                        if (strcasecmp($subject, $fromUsername) === 0) {
                            $bot->reply($change > 0 ? 'Don\'t be a weasel.' : 'Aw, don\'t be so hard on yourself.');
                            continue;
                        }

                        if (!isset($isProcessed[$subject])) {
                            $value = $entity->addKarma($subject, static::TYPE_USER, $chatId, $change);
                            $isProcessed[$subject] = true;
                        }
                    } else {
                        if ($match[2]) {
                            $subject = $match[2];
                        } elseif ($match[3]) {
                            $subject = $match[3];
                        } elseif ($match[5]) {
                            $subject = $match[5];
                        }

                        if (!isset($isProcessed[$subject])) {
                            $value = $entity->addKarma($subject, static::TYPE_THING, $chatId, $change);
                            $isProcessed[$subject] = true;
                        }
                    }

                    // Skip values we haven't processed
                    if ($value === null) {
                        continue;
                    }

                    $possessive = $subject . '\'' . ($subject[strlen($subject) - 1] === 's' ? '' : 's');
                    $changed = ($change > 0 ? 'increased' : 'decreased');
                    $line = $possessive . ' karma has ' . $changed . ' to ' . $value;
                    if ($maxed) {
                        $line .= ' (Buzzkill Mode™ has enforced a maximum change of 5 points)';
                    }
                    $line .= '.';

                    $bot->reply($line);
                }
            }
        });

        return $this;
    }

    /**
     * -------------------------------------------------------------------------
     * Getters and Setters
     * -------------------------------------------------------------------------.
     */

    /**
     * Get the Id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the Id.
     *
     * @param int $id The Id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the Name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the Name.
     *
     * @param string $name The Name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the Karma.
     *
     * @return int
     */
    public function getKarma()
    {
        return $this->karma;
    }

    /**
     * Set the Karma.
     *
     * @param int $karma The Karma
     *
     * @return self
     */
    public function setKarma($karma)
    {
        $this->karma = $karma;

        return $this;
    }

    /**
     * Get the chat id.
     *
     * @return string
     */
    public function getChat()
    {
        return $this->chat;
    }

    /**
     * Set the chat id.
     *
     * @param string $chat The chat id
     *
     * @return self
     */
    public function setChat($chat)
    {
        $this->chat = $chat;

        return $this;
    }

    /**
     * Find Functions.
     */

    /**
     * Find the karma for an entity by name.
     *
     * @param string $name The name of the thing
     * @param string $type The type
     * @param int    $chat The chat id
     *
     * @return self
     */
    public static function findByName($name, $type, $chat)
    {
        return self::getRepository()->findOneBy([
      'name' => $name,
      'type' => $type,
      'chat' => $chat,
    ]);
    }

    /**
     * Get the top results.
     *
     * @param string $type       One of self::TYPE_*
     * @param int    $chat       The chat id
     * @param int    $maxResults The number of results to return
     *
     * @return array of self
     */
    public static function getTopResults($type, $chat, $maxResults = 10)
    {
        $qb = parent::getEntityManager()->createQueryBuilder();
        $qb->select('Karma')
       ->from(__CLASS__, 'Karma')
       ->where('Karma.type = :type')
       ->andWhere('Karma.chat = :chat')
       ->orderBy('Karma.karma', 'DESC')
       ->setMaxResults($maxResults)
       ->setParameter('type', $type)
       ->setParameter('chat', $chat);

        return $qb->getQuery()->getResult();
    }

    /**
     * Utility Functions.
     */

    /**
     * Add karma to a thing or user.
     *
     * @param string $subject The subject
     * @param string $type    The type, either thing or user
     * @param string $chatId  The chat id
     * @param int    $change  The karma delta, positive or negative
     *
     * @return bool
     */
    protected function addKarma($subject, $type, $chatId, $change)
    {
        $karma = static::findByName($subject, $type, $chatId);
        if (!$karma) {
            $stmt = parent::getEntityManager()->getConnection()
        ->prepare('INSERT INTO karma (name, type, chat_id, karma) VALUES (:name, :type, :chat_id, :karma)');
            $stmt->bindValue('name', $subject);
            $stmt->bindValue('type', $type);
            $stmt->bindValue('chat_id', $chatId);
            $stmt->bindValue('karma', $change);

            if (!$stmt->execute()) {
                return;
            }
        } else {
            $change += $karma->getKarma();

            $qb = parent::getEntityManager()->createQueryBuilder();
            if ($change === 0) {
                $qb->delete(__CLASS__, 'Karma')
          ->where('Karma.id = :id')
          ->setParameter('id', $karma->getId());
            } else {
                $qb->update(__CLASS__, 'Karma')
           ->set('Karma.karma', $change)
           ->where('Karma.name = :name')
           ->andWhere('Karma.type = :type')
           ->andWhere('Karma.chat = :chat_id')
           ->setParameter('name', $subject)
           ->setParameter('type', $type)
           ->setParameter('chat_id', $chatId);
            }

            if (!$qb->getQuery()->execute()) {
                return;
            }
        }

        return $change;
    }
}

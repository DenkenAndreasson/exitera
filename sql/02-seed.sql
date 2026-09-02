
-- Alla konton har losenordet: test1234


SET NAMES utf8mb4;


-- 1 = sajt-admin (aldrig med i nagon guild)
-- 2,3,4 = Red Rose: leader, general, grunt
-- 5 = leader for Black Knights
-- 6 = utan guild, har en vantande ansokan till Red Rose
INSERT INTO users (id, first_name, last_name, email, password_hash, character_name, is_admin) VALUES
(1, 'Admin',  'Adminsson', 'admin@ot.local',    '$2y$12$Blnc8/Z7XIqC9hab2thFXe0IUu7z40/mEOYjc0.qyQ3.OUvnIlblm', 'GM Exitera',   TRUE),
(2, 'Erik',   'Svensson',  'leader@ot.local',   '$2y$12$F7hjGlDnwMhaw2QVKJaYyOapBPe5fuS1nUKqKTtCaejRWefHn7Bbi', 'Bloodrage',    FALSE),
(3, 'Anna',   'Lind',      'general@ot.local',  '$2y$12$kLT6RDW5IOJqO2bW4N8NyecvSB6ndWJS3EoxZjTMqGeCsT1gxuc.i', 'Shadowmourne', FALSE),
(4, 'Johan',  'Berg',      'grunt@ot.local',    '$2y$12$b3CjDrwjJLrFev481yJFLeul.Yz03KYYeU8fkjqQwYsmvCL1WmVce', 'Newbie Nick',  FALSE),
(5, 'Maria',  'Ek',        'leader2@ot.local',  '$2y$12$GsDu3HQtOEDXSTQu39OfG.GZc.pwXCXlRbs/28rts7ZrtbfF8EnUO', 'Ironfist',     FALSE),
(6, 'Oskar',  'Nyman',     'sokande@ot.local',  '$2y$12$kePbrolZJybU0cu41tYN7OwJjHxkArch4S4M293fdHyiBR73tMJN.', 'Rookie',       FALSE);


INSERT INTO groups (id, name, description, type, created_by) VALUES
(1, 'Leveling',         'Tips och rutter för att levla effektivt.',            'community', 1),
(2, 'Questing',         'Hjälp med quests, bossar och belöningar.',            'community', 1),
(3, 'Player vs Player', 'Diskussion om PvP, war och taktik.',                  'community', 1),
(4, 'Bug Reports',      'Rapportera buggar och problem på servern.',           'community', 1),
(5, 'Off-topic',        'Allt som inte passar någon annanstans.',              'community', 1),
(6, 'Red Rose',         'Sveriges äldsta guild på servern. Vi raidar fredagar.', 'guild',    2),
(7, 'Black Knights',    'PvP-fokuserad guild. Endast erfarna spelare.',        'guild',     5);


INSERT INTO group_members (group_id, user_id, role) VALUES
(6, 2, 'leader'),
(6, 3, 'general'),
(6, 4, 'grunt'),
(7, 5, 'leader');


INSERT INTO applications (group_id, user_id, status) VALUES
(6, 6, 'pending');


INSERT INTO topics (id, group_id, user_id, title) VALUES
(1, 1, 4, 'Bästa stället att levla 20-40?'),
(2, 4, 6, 'Dörren i Thais bank går inte att öppna'),
(3, 6, 2, 'Raid på fredag 20:00'),
(4, 6, 3, 'Nya regler för loot');


INSERT INTO posts (topic_id, user_id, body) VALUES
(1, 4, 'Jag har fastnat på level 24 och vet inte vart jag ska ta vägen. Några tips?'),
(1, 2, 'Rotworm caves är fortfarande bäst i det spannet. Ta med dig massa food.'),
(1, 3, 'Håller med, men gå i par. Ensam dör man på för mycket där nere.'),
(2, 6, 'Står precis framför dörren men den reagerar inte när jag klickar. Någon annan som ser detta?'),
(3, 2, 'Vi kör raid nu på fredag 20:00. Alla som kan bör dyka upp, vi behöver minst 8 personer.'),
(3, 3, 'Jag är med. Tar med extra potions till alla som behöver.'),
(3, 4, 'Första gången för mig, men jag kommer!'),
(4, 3, 'Från och med nu delar vi loot jämnt mellan alla som deltog i raiden. Inga undantag.');

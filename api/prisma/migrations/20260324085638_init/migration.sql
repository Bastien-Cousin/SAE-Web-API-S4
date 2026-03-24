-- CreateTable
CREATE TABLE "Team" (
    "pageid" BIGINT NOT NULL,
    "pagename" TEXT,
    "namespace" INTEGER,
    "objectname" TEXT NOT NULL,
    "name" TEXT,
    "locations" JSONB,
    "region" TEXT,
    "logo" TEXT,
    "logourl" TEXT,
    "logodark" TEXT,
    "logodarkurl" TEXT,
    "textlesslogourl" TEXT,
    "textlesslogodarkurl" TEXT,
    "status" TEXT,
    "createdate" DATE,
    "disbanddate" DATE,
    "earnings" BIGINT,
    "earningsbyyear" JSONB,
    "template" TEXT,
    "links" JSONB,
    "extradata" JSONB,
    "wiki" TEXT,

    CONSTRAINT "Team_pkey" PRIMARY KEY ("pageid")
);

-- CreateTable
CREATE TABLE "Player" (
    "pageid" BIGINT NOT NULL,
    "pagename" TEXT,
    "namespace" INTEGER,
    "objectname" TEXT NOT NULL,
    "id" TEXT,
    "alternateid" TEXT,
    "name" TEXT,
    "localizedname" TEXT,
    "type" TEXT,
    "nationality" TEXT,
    "nationality2" TEXT,
    "nationality3" TEXT,
    "region" TEXT,
    "birthdate" DATE,
    "deathdate" DATE,
    "teampagename" TEXT,
    "teamtemplate" TEXT,
    "links" JSONB,
    "status" TEXT,
    "earnings" INTEGER,
    "earningsbyyear" JSONB,
    "extradata" JSONB,
    "wiki" TEXT,
    "team_id" BIGINT,

    CONSTRAINT "Player_pkey" PRIMARY KEY ("pageid")
);

-- CreateTable
CREATE TABLE "Match" (
    "pageid" BIGINT NOT NULL,
    "pagename" TEXT,
    "namespace" INTEGER,
    "objectname" TEXT,
    "match2id" TEXT,
    "match2bracketid" TEXT,
    "status" TEXT,
    "winner" TEXT,
    "walkover" TEXT,
    "resulttype" TEXT,
    "finished" BOOLEAN DEFAULT false,
    "mode" TEXT,
    "type" TEXT,
    "section" TEXT,
    "game" TEXT,
    "patch" TEXT,
    "links" JSONB,
    "bestof" INTEGER,
    "date" TIMESTAMP(3),
    "dateexact" BOOLEAN DEFAULT false,
    "stream" JSONB,
    "vod" TEXT,
    "tournament" TEXT,
    "parent" TEXT,
    "tickername" TEXT,
    "shortname" TEXT,
    "series" TEXT,
    "icon" TEXT,
    "iconurl" TEXT,
    "icondark" TEXT,
    "icondarkurl" TEXT,
    "liquipediatier" TEXT,
    "liquipediatiertype" TEXT,
    "publishertier" TEXT,
    "extradata" JSONB,
    "match2bracketdata" JSONB,
    "match2games" JSONB,
    "match2opponents" JSONB,
    "wiki" TEXT,
    "team1_id" BIGINT,
    "team2_id" BIGINT,

    CONSTRAINT "Match_pkey" PRIMARY KEY ("pageid")
);

-- CreateTable
CREATE TABLE "MatchTeam" (
    "match_id" BIGINT NOT NULL,
    "team_id" BIGINT NOT NULL,

    CONSTRAINT "MatchTeam_pkey" PRIMARY KEY ("match_id","team_id")
);

-- CreateTable
CREATE TABLE "MatchPlayer" (
    "match_id" BIGINT NOT NULL,
    "player_id" BIGINT NOT NULL,

    CONSTRAINT "MatchPlayer_pkey" PRIMARY KEY ("match_id","player_id")
);

-- CreateIndex
CREATE UNIQUE INDEX "Team_objectname_key" ON "Team"("objectname");

-- CreateIndex
CREATE UNIQUE INDEX "Player_objectname_key" ON "Player"("objectname");

-- CreateIndex
CREATE INDEX "Player_team_id_idx" ON "Player"("team_id");

-- CreateIndex
CREATE INDEX "Match_team1_id_idx" ON "Match"("team1_id");

-- CreateIndex
CREATE INDEX "Match_team2_id_idx" ON "Match"("team2_id");

-- CreateIndex
CREATE INDEX "Match_date_idx" ON "Match"("date");

-- CreateIndex
CREATE INDEX "MatchTeam_team_id_idx" ON "MatchTeam"("team_id");

-- CreateIndex
CREATE INDEX "MatchPlayer_player_id_idx" ON "MatchPlayer"("player_id");

-- AddForeignKey
ALTER TABLE "Player" ADD CONSTRAINT "Player_team_id_fkey" FOREIGN KEY ("team_id") REFERENCES "Team"("pageid") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Match" ADD CONSTRAINT "Match_team1_id_fkey" FOREIGN KEY ("team1_id") REFERENCES "Team"("pageid") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Match" ADD CONSTRAINT "Match_team2_id_fkey" FOREIGN KEY ("team2_id") REFERENCES "Team"("pageid") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "MatchTeam" ADD CONSTRAINT "MatchTeam_match_id_fkey" FOREIGN KEY ("match_id") REFERENCES "Match"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "MatchTeam" ADD CONSTRAINT "MatchTeam_team_id_fkey" FOREIGN KEY ("team_id") REFERENCES "Team"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "MatchPlayer" ADD CONSTRAINT "MatchPlayer_match_id_fkey" FOREIGN KEY ("match_id") REFERENCES "Match"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "MatchPlayer" ADD CONSTRAINT "MatchPlayer_player_id_fkey" FOREIGN KEY ("player_id") REFERENCES "Player"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

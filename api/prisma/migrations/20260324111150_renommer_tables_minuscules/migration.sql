/*
  Warnings:

  - You are about to drop the `Match` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `MatchPlayer` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `MatchTeam` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `Player` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `Team` table. If the table is not empty, all the data it contains will be lost.

*/
-- DropForeignKey
ALTER TABLE "Match" DROP CONSTRAINT "Match_team1_id_fkey";

-- DropForeignKey
ALTER TABLE "Match" DROP CONSTRAINT "Match_team2_id_fkey";

-- DropForeignKey
ALTER TABLE "MatchPlayer" DROP CONSTRAINT "MatchPlayer_match_id_fkey";

-- DropForeignKey
ALTER TABLE "MatchPlayer" DROP CONSTRAINT "MatchPlayer_player_id_fkey";

-- DropForeignKey
ALTER TABLE "MatchTeam" DROP CONSTRAINT "MatchTeam_match_id_fkey";

-- DropForeignKey
ALTER TABLE "MatchTeam" DROP CONSTRAINT "MatchTeam_team_id_fkey";

-- DropForeignKey
ALTER TABLE "Player" DROP CONSTRAINT "Player_team_id_fkey";

-- DropTable
DROP TABLE "Match";

-- DropTable
DROP TABLE "MatchPlayer";

-- DropTable
DROP TABLE "MatchTeam";

-- DropTable
DROP TABLE "Player";

-- DropTable
DROP TABLE "Team";

-- CreateTable
CREATE TABLE "team" (
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

    CONSTRAINT "team_pkey" PRIMARY KEY ("pageid")
);

-- CreateTable
CREATE TABLE "player" (
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

    CONSTRAINT "player_pkey" PRIMARY KEY ("pageid")
);

-- CreateTable
CREATE TABLE "match" (
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

    CONSTRAINT "match_pkey" PRIMARY KEY ("pageid")
);

-- CreateTable
CREATE TABLE "matchteam" (
    "match_id" BIGINT NOT NULL,
    "team_id" BIGINT NOT NULL,

    CONSTRAINT "matchteam_pkey" PRIMARY KEY ("match_id","team_id")
);

-- CreateTable
CREATE TABLE "matchplayer" (
    "match_id" BIGINT NOT NULL,
    "player_id" BIGINT NOT NULL,

    CONSTRAINT "matchplayer_pkey" PRIMARY KEY ("match_id","player_id")
);

-- CreateIndex
CREATE UNIQUE INDEX "team_objectname_key" ON "team"("objectname");

-- CreateIndex
CREATE UNIQUE INDEX "player_objectname_key" ON "player"("objectname");

-- CreateIndex
CREATE INDEX "player_team_id_idx" ON "player"("team_id");

-- CreateIndex
CREATE INDEX "match_team1_id_idx" ON "match"("team1_id");

-- CreateIndex
CREATE INDEX "match_team2_id_idx" ON "match"("team2_id");

-- CreateIndex
CREATE INDEX "match_date_idx" ON "match"("date");

-- CreateIndex
CREATE INDEX "matchteam_team_id_idx" ON "matchteam"("team_id");

-- CreateIndex
CREATE INDEX "matchplayer_player_id_idx" ON "matchplayer"("player_id");

-- AddForeignKey
ALTER TABLE "player" ADD CONSTRAINT "player_team_id_fkey" FOREIGN KEY ("team_id") REFERENCES "team"("pageid") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "match" ADD CONSTRAINT "match_team1_id_fkey" FOREIGN KEY ("team1_id") REFERENCES "team"("pageid") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "match" ADD CONSTRAINT "match_team2_id_fkey" FOREIGN KEY ("team2_id") REFERENCES "team"("pageid") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "matchteam" ADD CONSTRAINT "matchteam_match_id_fkey" FOREIGN KEY ("match_id") REFERENCES "match"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "matchteam" ADD CONSTRAINT "matchteam_team_id_fkey" FOREIGN KEY ("team_id") REFERENCES "team"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "matchplayer" ADD CONSTRAINT "matchplayer_match_id_fkey" FOREIGN KEY ("match_id") REFERENCES "match"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "matchplayer" ADD CONSTRAINT "matchplayer_player_id_fkey" FOREIGN KEY ("player_id") REFERENCES "player"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

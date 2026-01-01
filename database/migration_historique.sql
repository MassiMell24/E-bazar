-- Script de migration manuelle pour ajouter les colonnes d'historique
-- Exécutez ce script dans phpMyAdmin ou via MySQL CLI

-- Vérifier et ajouter buyer_confirmed_reception
ALTER TABLE ads ADD COLUMN IF NOT EXISTS buyer_confirmed_reception BOOLEAN DEFAULT 0 AFTER sold_delivery_mode;

-- Vérifier et ajouter buyer_deleted (pour que l'acheteur puisse supprimer de sa vue)
ALTER TABLE ads ADD COLUMN IF NOT EXISTS buyer_deleted BOOLEAN DEFAULT 0 AFTER buyer_confirmed_reception;

-- Vérifier et ajouter seller_archived (pour que le vendeur puisse supprimer de sa vue)
ALTER TABLE ads ADD COLUMN IF NOT EXISTS seller_archived BOOLEAN DEFAULT 0 AFTER buyer_deleted;

-- Supprimer l'ancienne colonne buyer_archived si elle existe
-- ALTER TABLE ads DROP COLUMN IF EXISTS buyer_archived;

-- Vérifier les colonnes
SHOW COLUMNS FROM ads;

package com.wyldionstudios.ninjaguy.files;

import java.io.*;

import com.badlogic.gdx.Gdx;

import com.wyldionstudios.ninjaguy.config.Settings;

public class BinaryWriter {
	protected File file;
	protected FileOutputStream fp;
	protected DataOutputStream stream;
	
	public BinaryWriter(File file) {
		this.file = file;
	}
	
	public BinaryWriter(String filename) {
		this.file = new File(filename);
	}
	
	public boolean open() {
		try {
			fp = new FileOutputStream(this.file);
			stream = new DataOutputStream(fp);
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryWriter open ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}
	
	public boolean close() {
		try {
			if (fp != null) {
				fp.close();
				stream.close();
				
				return true;
			}
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryWriter close ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}
	
	public boolean writeByte(int value) {
		return writeIntToBytes(value, 1);
	}
	
	public boolean writeShort(int value) {
		return writeIntToBytes(value, 2);
	}
	
	public boolean writeLong(int value) {
		return writeIntToBytes(value, 4);
	}
	
	public boolean writeFloat(int value) {
		return writeIntToBytes(value, 4);
	}
	
	public boolean writeDouble(int value) {
		try {
			byte[] b = new byte[8];
			stream.write(b);
			
			return true;
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryWriter writeDouble ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}
	
	public boolean writeString(String string) {
		try {
			stream.writeByte(string.length());
			for(int i=0; i<string.length(); i++) {
				stream.writeByte(string.charAt(i));
			}
			
			return true;
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryWriter writeString ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}
	
	public boolean writeBoolean(boolean value) {
		try {
			stream.writeBoolean(value);
			
			return true;
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryWriter writeBoolean ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}

	protected boolean writeIntToBytes(int value, int length) {
		try {
			byte[] b = new byte[length];
			
			for(int i=0; i<length; i++) {
				b[i] = (byte) ((value >> (i * 8)) & 0xFF);
			}

			stream.write(b);
			
			return true;
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryWriter writeIntToBytes ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}
}
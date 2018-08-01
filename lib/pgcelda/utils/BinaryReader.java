package com.wyldionstudios.ninjaguy.files;

import java.io.*;
import java.nio.*;

import com.badlogic.gdx.Gdx;

import com.wyldionstudios.ninjaguy.config.Settings;

public class BinaryReader {
	protected File file;
	protected FileInputStream fp;
	protected DataInputStream stream;
	
	public BinaryReader(File file) {
		this.file = file;
	}
	
	public BinaryReader(String filename) {
		this.file = new File(filename);
	}
	
	public boolean open() {
		try {
			fp = new FileInputStream(this.file);
			stream = new DataInputStream(fp);
			if (stream.available() > 0) {
				return true;
			}
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryReader open ERROR: " + e.getMessage() + " - " + this.file.getName());
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
			Gdx.app.error(Settings.TAG, "BinaryReader close ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}
	
	public int readByte() {
		return readBytesForInt(1);
	}
	
	public int readShort() {
		return readBytesForInt(2);
	}
	
	public int readLong() {
		return readBytesForInt(4);
	}
	
	/*public float readFloat() {
		return Float.intBitsToFloat(readLong());
	}*/
	
	public int readFloat() {
		float value = Float.intBitsToFloat(readBytesForInt(4));
		
		return (int) Math.ceil(value);
	}
	
	public int readDouble() {
		try {
			byte[] b = new byte[8];
			stream.read(b);
			
			double value = ByteBuffer.wrap(b).order(ByteOrder.LITTLE_ENDIAN).getDouble();
			
			return (int) Math.ceil(value);
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryReader readDouble ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return -1;
	}
	
	public String readString() {
		String string = "";
		try {
			int length = (int) stream.readByte();
			for(int i=0; i<length; i++) {
				string += ((char) stream.readByte());
			}
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryReader readString ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return string;
	}
	
	public boolean readBoolean() {
		try {
			return stream.readBoolean();
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryReader readBoolean ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return false;
	}
	
	protected int readBytesForInt(int length) {
		try {
			byte[] b = new byte[length];
			stream.read(b);
			
			int value = 0;
			for(int i=0; i<length; i++) {
				value |= (b[i] & 0xFF) << (i * 8);
			}
			
			return value;
		}
		catch (IOException e) {
			Gdx.app.error(Settings.TAG, "BinaryReader readBytesForInt ERROR: " + e.getMessage() + " - " + this.file.getName());
		}
		
		return -1;
	}
}